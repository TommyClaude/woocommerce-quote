<?php
/**
 * REST endpoints backing the React admin app: settings get/save and the
 * dashboard statistics.
 *
 * @package WooCommerce_Quote
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WCQ_REST
 */
class WCQ_REST {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	const REST_NAMESPACE = 'wcq/v1';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Permission check shared by every route.
	 *
	 * @return bool
	 */
	public function permission() {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Register the REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			'/settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'permission' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'save_settings' ),
					'permission_callback' => array( $this, 'permission' ),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/stats',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_stats' ),
				'permission_callback' => array( $this, 'permission' ),
			)
		);
	}

	/**
	 * GET /settings — current settings.
	 *
	 * @return WP_REST_Response
	 */
	public function get_settings() {
		return rest_ensure_response( WCQ_Settings::get_all() );
	}

	/**
	 * POST /settings — sanitize and save.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function save_settings( WP_REST_Request $request ) {
		$input = $request->get_json_params();
		if ( ! is_array( $input ) ) {
			$input = $request->get_params();
		}

		$allowed  = array_keys( WCQ_Settings::get_defaults() );
		$filtered = array_intersect_key( (array) $input, array_flip( $allowed ) );

		$saved = WCQ_Settings::update( $filtered );

		return rest_ensure_response(
			array(
				'success'  => true,
				'settings' => $saved,
			)
		);
	}

	/**
	 * GET /stats — dashboard statistics.
	 *
	 * @return WP_REST_Response
	 */
	public function get_stats() {
		$status_labels = WCQ_CPT::get_statuses();
		$statuses      = array_keys( $status_labels );

		// Counts per status.
		$counts = (array) wp_count_posts( WCQ_CPT::POST_TYPE );
		$by     = array();
		$total  = 0;
		foreach ( $statuses as $status ) {
			$by[ $status ] = isset( $counts[ $status ] ) ? (int) $counts[ $status ] : 0;
			$total        += $by[ $status ];
		}

		$accepted   = $by['wcq-accepted'];
		$conversion = $total > 0 ? (int) round( ( $accepted / $total ) * 100 ) : 0;

		return rest_ensure_response(
			array(
				'totals'     => array(
					'total'      => $total,
					'new'        => $by['wcq-new'],
					'quoted'     => $by['wcq-quoted'],
					'accepted'   => $accepted,
					'rejected'   => $by['wcq-rejected'],
					'expired'    => $by['wcq-expired'],
					'conversion' => $conversion,
				),
				'by_status'  => $by,
				'trend'      => $this->get_trend( $statuses ),
				'recent'     => $this->get_recent( $statuses, $status_labels ),
				'list_url'   => admin_url( 'edit.php?post_type=' . WCQ_CPT::POST_TYPE ),
				'currency'   => get_woocommerce_currency_symbol(),
			)
		);
	}

	/**
	 * Build a 30-day daily count series.
	 *
	 * @param string[] $statuses Quote statuses.
	 * @return array<int,array{date:string,label:string,count:int}>
	 */
	protected function get_trend( array $statuses ) {
		global $wpdb;

		$base  = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- local-day bucketing.
		$since = gmdate( 'Y-m-d 00:00:00', strtotime( '-29 days', $base ) );

		$placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );
		$query_args   = array_merge( array( WCQ_CPT::POST_TYPE ), $statuses, array( $since ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(post_date) AS d, COUNT(*) AS c
				 FROM {$wpdb->posts}
				 WHERE post_type = %s AND post_status IN ($placeholders) AND post_date >= %s
				 GROUP BY DATE(post_date)",
				$query_args
			)
		);
		// phpcs:enable

		$map = array();
		foreach ( (array) $rows as $row ) {
			$map[ $row->d ] = (int) $row->c;
		}

		$trend = array();
		for ( $i = 29; $i >= 0; $i-- ) {
			$timestamp = strtotime( "-{$i} days", $base );
			$day       = gmdate( 'Y-m-d', $timestamp );
			$trend[]   = array(
				'date'  => $day,
				'label' => wp_date( 'M j', $timestamp ),
				'count' => isset( $map[ $day ] ) ? $map[ $day ] : 0,
			);
		}

		return $trend;
	}

	/**
	 * The most recent quote requests.
	 *
	 * @param string[] $statuses Quote statuses.
	 * @param array    $labels   Status slug => label.
	 * @return array
	 */
	protected function get_recent( array $statuses, array $labels ) {
		$posts = get_posts(
			array(
				'post_type'   => WCQ_CPT::POST_TYPE,
				'post_status' => $statuses,
				'numberposts' => 8,
				'orderby'     => 'date',
				'order'       => 'DESC',
			)
		);

		$out = array();
		foreach ( $posts as $post ) {
			$customer = (array) get_post_meta( $post->ID, '_wcq_customer', true );
			$out[]    = array(
				'id'           => $post->ID,
				'customer'     => isset( $customer['name'] ) ? $customer['name'] : '',
				'email'        => isset( $customer['email'] ) ? $customer['email'] : '',
				'status'       => $post->post_status,
				'status_label' => isset( $labels[ $post->post_status ] ) ? $labels[ $post->post_status ] : $post->post_status,
				'date'         => get_the_date( '', $post ),
				'edit_link'    => get_edit_post_link( $post->ID, 'raw' ),
			);
		}

		return $out;
	}
}
