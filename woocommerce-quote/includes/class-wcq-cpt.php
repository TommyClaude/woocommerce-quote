<?php
/**
 * Custom post type + statuses for quote requests.
 *
 * Each quote request a customer submits is stored as one `wcq_quote` post.
 * Line items, customer fields and totals live in post meta (see PLAN.md §4).
 *
 * @package WooCommerce_Quote
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WCQ_CPT
 */
class WCQ_CPT {

	/**
	 * Post type slug.
	 *
	 * @var string
	 */
	const POST_TYPE = 'wcq_quote';

	/**
	 * Constructor — hook registration onto `init`.
	 */
	public function __construct() {
		// Statuses must be registered before the post type that uses them.
		add_action( 'init', array( __CLASS__, 'register_post_statuses' ), 9 );
		add_action( 'init', array( __CLASS__, 'register_post_type' ), 10 );
	}

	/**
	 * The custom quote statuses.
	 *
	 * Keys are the registered post-status slugs; values are the human labels.
	 *
	 * @return array<string,string>
	 */
	public static function get_statuses() {
		return array(
			'wcq-new'      => _x( 'New', 'Quote status', 'woocommerce-quote' ),
			'wcq-quoted'   => _x( 'Quoted', 'Quote status', 'woocommerce-quote' ),
			'wcq-accepted' => _x( 'Accepted', 'Quote status', 'woocommerce-quote' ),
			'wcq-rejected' => _x( 'Rejected', 'Quote status', 'woocommerce-quote' ),
			'wcq-expired'  => _x( 'Expired', 'Quote status', 'woocommerce-quote' ),
		);
	}

	/**
	 * Register the `wcq_quote` post type.
	 *
	 * Not publicly queryable: quotes are private records managed in wp-admin by
	 * users with the `manage_woocommerce` capability. New records are created
	 * from the front-end submission handler, never via "Add New".
	 *
	 * @return void
	 */
	public static function register_post_type() {
		$labels = array(
			'name'               => _x( 'Quote Requests', 'Post type general name', 'woocommerce-quote' ),
			'singular_name'      => _x( 'Quote Request', 'Post type singular name', 'woocommerce-quote' ),
			'menu_name'          => _x( 'Quote Requests', 'Admin Menu text', 'woocommerce-quote' ),
			'name_admin_bar'     => _x( 'Quote Request', 'Add New on Toolbar', 'woocommerce-quote' ),
			'all_items'          => __( 'Quote Requests', 'woocommerce-quote' ),
			'edit_item'          => __( 'Quote Request', 'woocommerce-quote' ),
			'view_item'          => __( 'View Quote Request', 'woocommerce-quote' ),
			'search_items'       => __( 'Search Quote Requests', 'woocommerce-quote' ),
			'not_found'          => __( 'No quote requests found.', 'woocommerce-quote' ),
			'not_found_in_trash' => __( 'No quote requests found in Trash.', 'woocommerce-quote' ),
			'items_list'         => __( 'Quote requests list', 'woocommerce-quote' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => false,
			'show_ui'             => true,
			// Added as a submenu of the "WooCommerce Quote" menu by WCQ_Admin.
			'show_in_menu'        => false,
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'show_in_rest'        => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'hierarchical'        => false,
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'menu_icon'           => 'dashicons-format-status',
			'menu_position'       => 58,
			'supports'            => array( 'title' ),
			'map_meta_cap'        => true,
			'capability_type'     => 'post',
			// IMPORTANT: only map *primitive* caps to manage_woocommerce. Never map
			// the meta caps (edit_post/read_post/delete_post) to a shared cap like
			// manage_woocommerce — WordPress would then register manage_woocommerce
			// as a meta capability globally, breaking current_user_can() for it
			// everywhere (it would hide WooCommerce's own menus). With map_meta_cap
			// enabled, WP maps the meta caps to these primitives automatically.
			'capabilities'        => array(
				'create_posts'           => 'do_not_allow',
				'edit_posts'             => 'manage_woocommerce',
				'edit_others_posts'      => 'manage_woocommerce',
				'edit_published_posts'   => 'manage_woocommerce',
				'edit_private_posts'     => 'manage_woocommerce',
				'publish_posts'          => 'manage_woocommerce',
				'read_private_posts'     => 'manage_woocommerce',
				'delete_posts'           => 'manage_woocommerce',
				'delete_others_posts'    => 'manage_woocommerce',
				'delete_published_posts' => 'manage_woocommerce',
				'delete_private_posts'   => 'manage_woocommerce',
			),
		);

		/**
		 * Filter the arguments used to register the quote post type.
		 *
		 * @param array $args Register-post-type arguments.
		 */
		$args = apply_filters( 'wcq_post_type_args', $args );

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Register the custom quote statuses.
	 *
	 * @return void
	 */
	public static function register_post_statuses() {
		foreach ( self::get_statuses() as $status => $label ) {
			register_post_status(
				$status,
				array(
					'label'                     => $label,
					'public'                    => false,
					'internal'                  => false,
					'private'                   => true,
					'exclude_from_search'       => true,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: number of quote requests. */
					'label_count'               => _n_noop(
						$label . ' <span class="count">(%s)</span>',
						$label . ' <span class="count">(%s)</span>',
						'woocommerce-quote'
					),
				)
			);
		}
	}
}
