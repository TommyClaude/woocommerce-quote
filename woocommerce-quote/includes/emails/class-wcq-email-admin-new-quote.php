<?php
/**
 * "New quote request" email sent to the store.
 *
 * @package WooCommerce_Quote
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Email' ) ) {
	return;
}

/**
 * Class WCQ_Email_Admin_New_Quote
 */
class WCQ_Email_Admin_New_Quote extends WC_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'wcq_admin_new_quote';
		$this->customer_email = false;
		$this->title          = __( 'New quote request', 'woocommerce-quote' );
		$this->description    = __( 'Sent to the store when a customer submits a quote request.', 'woocommerce-quote' );
		$this->template_base  = WCQ_PLUGIN_DIR . 'templates/';
		$this->template_html  = 'emails/admin-new-quote.php';
		$this->template_plain = 'emails/plain/admin-new-quote.php';
		$this->placeholders   = array( '{quote_id}' => '' );

		add_action( 'wcq_quote_created_notification', array( $this, 'trigger' ), 10, 1 );

		parent::__construct();

		// Default recipient: the plugin setting, else the saved option / admin email.
		$saved           = $this->get_option( 'recipient' );
		$this->recipient = $saved ? $saved : WCQ_Settings::get( 'recipient_emails' );
		if ( ! $this->recipient ) {
			$this->recipient = get_option( 'admin_email' );
		}
	}

	/**
	 * Default subject.
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return __( '[{site_title}] New quote request #{quote_id}', 'woocommerce-quote' );
	}

	/**
	 * Default heading.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'New quote request', 'woocommerce-quote' );
	}

	/**
	 * Send the email for a given quote.
	 *
	 * @param int $quote_id Quote post ID.
	 * @return void
	 */
	public function trigger( $quote_id ) {
		$this->setup_locale();

		$quote_id = absint( $quote_id );
		if ( $quote_id ) {
			$this->object                     = $quote_id;
			$this->placeholders['{quote_id}'] = $quote_id;
		}

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();
	}

	/**
	 * HTML content.
	 *
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html( $this->template_html, $this->template_args( false ), '', $this->template_base );
	}

	/**
	 * Plain-text content.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html( $this->template_plain, $this->template_args( true ), '', $this->template_base );
	}

	/**
	 * Shared template variables.
	 *
	 * @param bool $plain Whether this is the plain-text variant.
	 * @return array
	 */
	protected function template_args( $plain ) {
		$quote_id = absint( $this->object );

		return array(
			'quote_id'      => $quote_id,
			'customer'      => (array) get_post_meta( $quote_id, '_wcq_customer', true ),
			'items'         => (array) get_post_meta( $quote_id, '_wcq_items', true ),
			'totals'        => (array) get_post_meta( $quote_id, '_wcq_totals', true ),
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => true,
			'plain_text'    => $plain,
			'email'         => $this,
		);
	}
}
