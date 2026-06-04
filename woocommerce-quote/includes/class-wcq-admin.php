<?php
/**
 * wp-admin management of quote records on the native `wcq_quote` list table:
 * custom columns, a detail meta box, and a status control.
 *
 * The React admin app (Dashboard / Settings / About) is added separately; the
 * per-record CRUD stays on this robust native screen — see PLAN.md §2.5.
 *
 * @package WooCommerce_Quote
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WCQ_Admin
 */
class WCQ_Admin {

	/**
	 * Top-level menu slug (also the React app page).
	 *
	 * @var string
	 */
	const MENU_SLUG = 'wcq-dashboard';

	/**
	 * Constructor — register admin-only hooks.
	 */
	public function __construct() {
		if ( ! is_admin() ) {
			return;
		}

		$type = WCQ_CPT::POST_TYPE;

		add_filter( "manage_{$type}_posts_columns", array( $this, 'columns' ) );
		add_action( "manage_{$type}_posts_custom_column", array( $this, 'render_column' ), 10, 2 );
		add_filter( "manage_edit-{$type}_sortable_columns", array( $this, 'sortable_columns' ) );

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( "save_post_{$type}", array( $this, 'save_status' ), 10, 2 );

		add_filter( 'post_row_actions', array( $this, 'row_actions' ), 10, 2 );
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Register the top-level "WooCommerce Quote" menu + submenus.
	 *
	 * The React app renders on the top-level page; the native quote list is
	 * added as a submenu so per-record CRUD stays on the robust core screen.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_menu_page(
			__( 'WooCommerce Quote', 'woocommerce-quote' ),
			__( 'WooCommerce Quote', 'woocommerce-quote' ),
			'manage_woocommerce',
			self::MENU_SLUG,
			array( $this, 'render_app_page' ),
			'dashicons-format-status',
			58
		);

		// Rename the auto-created first submenu to "Dashboard".
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Dashboard', 'woocommerce-quote' ),
			__( 'Dashboard', 'woocommerce-quote' ),
			'manage_woocommerce',
			self::MENU_SLUG,
			array( $this, 'render_app_page' )
		);

		// Native quote-records list table.
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Quote Requests', 'woocommerce-quote' ),
			__( 'Quote Requests', 'woocommerce-quote' ),
			'manage_woocommerce',
			'edit.php?post_type=' . WCQ_CPT::POST_TYPE
		);
	}

	/**
	 * Render the React app mount point.
	 *
	 * @return void
	 */
	public function render_app_page() {
		echo '<div class="wrap"><div id="wcq-admin-app"></div></div>';
	}

	/**
	 * Whether we are on a screen for our post type.
	 *
	 * @return bool
	 */
	protected function is_quote_screen() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		return $screen && WCQ_CPT::POST_TYPE === $screen->post_type;
	}

	/**
	 * Enqueue assets: the React app on its page, plus the list-table styles.
	 *
	 * @param string $hook Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue( $hook = '' ) {
		if ( 'toplevel_page_' . self::MENU_SLUG === $hook ) {
			$this->enqueue_app();
		}

		if ( $this->is_quote_screen() ) {
			wp_enqueue_style( 'wcq-admin', WCQ_PLUGIN_URL . 'assets/css/wcq-admin.css', array(), WCQ_VERSION );
		}
	}

	/**
	 * Enqueue the compiled React app and hand it its bootstrap data.
	 *
	 * @return void
	 */
	public function enqueue_app() {
		$asset_path = WCQ_PLUGIN_DIR . 'build/index.asset.php';

		if ( ! file_exists( $asset_path ) ) {
			add_action( 'admin_notices', array( $this, 'build_missing_notice' ) );
			return;
		}

		$asset = include $asset_path;

		wp_enqueue_script(
			'wcq-admin-app',
			WCQ_PLUGIN_URL . 'build/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);
		wp_set_script_translations( 'wcq-admin-app', 'woocommerce-quote', WCQ_PLUGIN_DIR . 'languages' );

		if ( file_exists( WCQ_PLUGIN_DIR . 'build/index.css' ) ) {
			wp_enqueue_style(
				'wcq-admin-app',
				WCQ_PLUGIN_URL . 'build/index.css',
				array( 'wp-components' ),
				$asset['version']
			);
		}

		wp_localize_script( 'wcq-admin-app', 'wcqAdmin', $this->app_data() );
	}

	/**
	 * Notice shown when the admin app has not been built yet.
	 *
	 * @return void
	 */
	public function build_missing_notice() {
		echo '<div class="notice notice-warning"><p>';
		echo esc_html__( 'WooCommerce Quote: please run "npm install && npm run build" to compile the admin app.', 'woocommerce-quote' );
		echo '</p></div>';
	}

	/**
	 * Bootstrap data passed to the React app.
	 *
	 * @return array
	 */
	protected function app_data() {
		return array(
			'version'    => WCQ_VERSION,
			'supportUrl' => 'https://github.com/TommyClaude/woocommerce-quote/issues',
			'listUrl'    => admin_url( 'edit.php?post_type=' . WCQ_CPT::POST_TYPE ),
			'options'    => array(
				'categories' => $this->category_options(),
				'products'   => $this->product_options(),
				'roles'      => $this->role_options(),
				'pages'      => $this->page_options(),
			),
		);
	}

	/**
	 * Product category options for the scope token field.
	 *
	 * @return array<int,array{id:int,name:string}>
	 */
	protected function category_options() {
		$out   = array();
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'number'     => 300,
			)
		);
		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$out[] = array(
					'id'   => (int) $term->term_id,
					'name' => $term->name,
				);
			}
		}
		return $out;
	}

	/**
	 * A bounded list of products for the scope token field.
	 *
	 * @return array<int,array{id:int,name:string}>
	 */
	protected function product_options() {
		$out = array();
		if ( ! function_exists( 'wc_get_products' ) ) {
			return $out;
		}
		$products = wc_get_products(
			array(
				'limit'   => 100,
				'status'  => 'publish',
				'orderby' => 'title',
				'order'   => 'ASC',
				'return'  => 'objects',
			)
		);
		foreach ( $products as $product ) {
			$out[] = array(
				'id'   => $product->get_id(),
				'name' => $product->get_name(),
			);
		}
		return $out;
	}

	/**
	 * User-role options for the scope token field.
	 *
	 * @return array<int,array{id:string,name:string}>
	 */
	protected function role_options() {
		$out   = array();
		$roles = function_exists( 'wp_roles' ) ? wp_roles()->roles : array();
		foreach ( $roles as $slug => $role ) {
			$out[] = array(
				'id'   => $slug,
				'name' => translate_user_role( $role['name'] ),
			);
		}
		return $out;
	}

	/**
	 * Published pages for the quote-page selector.
	 *
	 * @return array<int,array{id:int,title:string}>
	 */
	protected function page_options() {
		$out   = array();
		$pages = get_pages( array( 'number' => 300 ) );
		foreach ( (array) $pages as $page ) {
			$out[] = array(
				'id'    => $page->ID,
				'title' => '' !== $page->post_title ? $page->post_title : sprintf( '#%d', $page->ID ),
			);
		}
		return $out;
	}

	/**
	 * Define the list-table columns.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function columns( $columns ) {
		$cb = isset( $columns['cb'] ) ? $columns['cb'] : '<input type="checkbox" />';

		return array(
			'cb'           => $cb,
			'title'        => __( 'Quote', 'woocommerce-quote' ),
			'wcq_customer' => __( 'Customer', 'woocommerce-quote' ),
			'wcq_items'    => __( 'Items', 'woocommerce-quote' ),
			'wcq_total'    => __( 'Total', 'woocommerce-quote' ),
			'wcq_status'   => __( 'Status', 'woocommerce-quote' ),
			'date'         => __( 'Date', 'woocommerce-quote' ),
		);
	}

	/**
	 * Keep the Date column sortable.
	 *
	 * @param array $columns Sortable columns.
	 * @return array
	 */
	public function sortable_columns( $columns ) {
		$columns['date'] = 'date';
		return $columns;
	}

	/**
	 * Render a custom column.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_column( $column, $post_id ) {
		switch ( $column ) {
			case 'wcq_customer':
				$customer = (array) get_post_meta( $post_id, '_wcq_customer', true );
				if ( ! empty( $customer['name'] ) ) {
					echo '<strong>' . esc_html( $customer['name'] ) . '</strong>';
				}
				if ( ! empty( $customer['email'] ) ) {
					echo '<br /><a href="' . esc_url( 'mailto:' . $customer['email'] ) . '">' . esc_html( $customer['email'] ) . '</a>';
				}
				if ( ! empty( $customer['company'] ) ) {
					echo '<br /><span class="wcq-muted">' . esc_html( $customer['company'] ) . '</span>';
				}
				break;

			case 'wcq_items':
				$items = get_post_meta( $post_id, '_wcq_items', true );
				$count = is_array( $items ) ? count( $items ) : 0;
				/* translators: %d: number of products. */
				echo esc_html( sprintf( _n( '%d product', '%d products', $count, 'woocommerce-quote' ), $count ) );
				break;

			case 'wcq_total':
				$totals   = (array) get_post_meta( $post_id, '_wcq_totals', true );
				$subtotal = isset( $totals['subtotal'] ) ? (float) $totals['subtotal'] : 0;
				echo $subtotal > 0 ? wp_kses_post( wc_price( $subtotal ) ) : '&mdash;';
				break;

			case 'wcq_status':
				$status   = get_post_status( $post_id );
				$statuses = WCQ_CPT::get_statuses();
				$label    = isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status;
				printf(
					'<span class="wcq-status-badge wcq-status-%1$s">%2$s</span>',
					esc_attr( $status ),
					esc_html( $label )
				);
				break;
		}
	}

	/**
	 * Remove inline/quick edit and view links (quotes aren't public).
	 *
	 * @param array   $actions Row actions.
	 * @param WP_Post $post    Post.
	 * @return array
	 */
	public function row_actions( $actions, $post ) {
		if ( WCQ_CPT::POST_TYPE === $post->post_type ) {
			unset( $actions['inline hide-if-no-js'], $actions['view'] );
		}
		return $actions;
	}

	/**
	 * Register the detail + status meta boxes.
	 *
	 * @return void
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'wcq_details',
			__( 'Quote details', 'woocommerce-quote' ),
			array( $this, 'render_details_box' ),
			WCQ_CPT::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'wcq_status',
			__( 'Status', 'woocommerce-quote' ),
			array( $this, 'render_status_box' ),
			WCQ_CPT::POST_TYPE,
			'side',
			'high'
		);
	}

	/**
	 * Render the read-only detail meta box.
	 *
	 * @param WP_Post $post Post.
	 * @return void
	 */
	public function render_details_box( $post ) {
		$customer = (array) get_post_meta( $post->ID, '_wcq_customer', true );
		$items    = (array) get_post_meta( $post->ID, '_wcq_items', true );
		$totals   = (array) get_post_meta( $post->ID, '_wcq_totals', true );
		$currency = isset( $totals['currency'] ) ? $totals['currency'] : get_woocommerce_currency();

		echo '<table class="wcq-detail-table widefat striped">';

		$rows = array(
			__( 'Name', 'woocommerce-quote' )    => isset( $customer['name'] ) ? $customer['name'] : '',
			__( 'Email', 'woocommerce-quote' )   => isset( $customer['email'] ) ? $customer['email'] : '',
			__( 'Phone', 'woocommerce-quote' )   => isset( $customer['phone'] ) ? $customer['phone'] : '',
			__( 'Company', 'woocommerce-quote' ) => isset( $customer['company'] ) ? $customer['company'] : '',
		);
		foreach ( $rows as $label => $value ) {
			if ( '' === $value ) {
				continue;
			}
			echo '<tr><th scope="row" style="width:140px;">' . esc_html( $label ) . '</th><td>';
			if ( __( 'Email', 'woocommerce-quote' ) === $label ) {
				echo '<a href="' . esc_url( 'mailto:' . $value ) . '">' . esc_html( $value ) . '</a>';
			} else {
				echo esc_html( $value );
			}
			echo '</td></tr>';
		}
		echo '</table>';

		if ( ! empty( $customer['message'] ) ) {
			echo '<h4>' . esc_html__( 'Message', 'woocommerce-quote' ) . '</h4>';
			echo '<p>' . nl2br( esc_html( $customer['message'] ) ) . '</p>';
		}

		echo '<h4>' . esc_html__( 'Requested products', 'woocommerce-quote' ) . '</h4>';
		echo '<table class="wcq-detail-table widefat striped">';
		echo '<thead><tr>';
		echo '<th>' . esc_html__( 'Product', 'woocommerce-quote' ) . '</th>';
		echo '<th>' . esc_html__( 'SKU', 'woocommerce-quote' ) . '</th>';
		echo '<th>' . esc_html__( 'Qty', 'woocommerce-quote' ) . '</th>';
		echo '<th>' . esc_html__( 'Price', 'woocommerce-quote' ) . '</th>';
		echo '<th>' . esc_html__( 'Line total', 'woocommerce-quote' ) . '</th>';
		echo '</tr></thead><tbody>';

		if ( empty( $items ) ) {
			echo '<tr><td colspan="5">' . esc_html__( 'No products recorded.', 'woocommerce-quote' ) . '</td></tr>';
		} else {
			foreach ( $items as $item ) {
				$name  = isset( $item['name'] ) ? $item['name'] : '';
				$sku   = isset( $item['sku'] ) ? $item['sku'] : '';
				$qty   = isset( $item['quantity'] ) ? (int) $item['quantity'] : 0;
				$price = isset( $item['price'] ) ? $item['price'] : '';
				$edit  = isset( $item['product_id'] ) ? get_edit_post_link( $item['product_id'] ) : '';

				echo '<tr>';
				echo '<td>';
				if ( $edit ) {
					echo '<a href="' . esc_url( $edit ) . '">' . esc_html( $name ) . '</a>';
				} else {
					echo esc_html( $name );
				}
				echo '</td>';
				echo '<td>' . esc_html( $sku ? $sku : '—' ) . '</td>';
				echo '<td>' . esc_html( $qty ) . '</td>';
				if ( '' === $price || null === $price ) {
					echo '<td>&mdash;</td><td>&mdash;</td>';
				} else {
					echo '<td>' . wp_kses_post( wc_price( $price, array( 'currency' => $currency ) ) ) . '</td>';
					echo '<td>' . wp_kses_post( wc_price( (float) $price * $qty, array( 'currency' => $currency ) ) ) . '</td>';
				}
				echo '</tr>';
			}
		}
		echo '</tbody>';

		if ( ! empty( $totals['subtotal'] ) ) {
			echo '<tfoot><tr><th colspan="4" style="text-align:right;">' . esc_html__( 'Estimated subtotal', 'woocommerce-quote' ) . '</th>';
			echo '<th>' . wp_kses_post( wc_price( (float) $totals['subtotal'], array( 'currency' => $currency ) ) ) . '</th></tr></tfoot>';
		}
		echo '</table>';
	}

	/**
	 * Render the status control meta box.
	 *
	 * @param WP_Post $post Post.
	 * @return void
	 */
	public function render_status_box( $post ) {
		$statuses = WCQ_CPT::get_statuses();
		$current  = get_post_status( $post->ID );
		if ( ! isset( $statuses[ $current ] ) ) {
			$current = 'wcq-new';
		}

		wp_nonce_field( 'wcq_save_status', 'wcq_status_nonce' );

		echo '<p><label for="wcq_status_select"><strong>' . esc_html__( 'Quote status', 'woocommerce-quote' ) . '</strong></label></p>';
		echo '<select name="wcq_status" id="wcq_status_select" class="widefat">';
		foreach ( $statuses as $value => $label ) {
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $value ),
				selected( $current, $value, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Use the Update button to save the status.', 'woocommerce-quote' ) . '</p>';
	}

	/**
	 * Persist the chosen status (forced after WordPress saves the post, so the
	 * default Publish box cannot reset our custom status).
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 * @return void
	 */
	public function save_status( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['wcq_status_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wcq_status_nonce'] ) ), 'wcq_save_status' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$new      = isset( $_POST['wcq_status'] ) ? sanitize_key( wp_unslash( $_POST['wcq_status'] ) ) : '';
		$statuses = WCQ_CPT::get_statuses();
		if ( ! isset( $statuses[ $new ] ) ) {
			return;
		}

		$old = get_post_status( $post_id );
		if ( $old === $new ) {
			return;
		}

		global $wpdb;
		$wpdb->update( $wpdb->posts, array( 'post_status' => $new ), array( 'ID' => $post_id ) );
		clean_post_cache( $post_id );

		/**
		 * Fires when a quote's status changes in wp-admin.
		 *
		 * @param int    $post_id Quote ID.
		 * @param string $new     New status.
		 * @param string $old     Previous status.
		 */
		do_action( 'wcq_quote_status_changed', $post_id, $new, $old );
	}
}
