<?php
/**
 * "Your quote" email sent to the customer with quoted prices + an accept link.
 *
 * @package WooCommerce_Quote
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Email' ) ) {
	return;
}

/**
 * Class WCQ_Email_Customer_Quote
 */
class WCQ_Email_Customer_Quote extends WC_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'wcq_customer_quote';
		$this->customer_email = true;
		$this->title          = __( 'Your quote', 'woocommerce-quote' );
		$this->description    = __( 'Sent to the customer with the quoted prices and a link to accept.', 'woocommerce-quote' );
		$this->template_base  = WCQ_PLUGIN_DIR . 'templates/';
		$this->template_html  = 'emails/customer-quote.php';
		$this->template_plain = 'emails/plain/customer-quote.php';
		$this->placeholders   = array( '{quote_id}' => '' );

		add_action( 'wcq_quote_sent_notification', array( $this, 'trigger' ), 10, 1 );

		parent::__construct();
	}

	/**
	 * Default subject.
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return __( 'Your quote #{quote_id}', 'woocommerce-quote' );
	}

	/**
	 * Default heading.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Your quote is ready', 'woocommerce-quote' );
	}

	/**
	 * Send the quote to the customer.
	 *
	 * @param int $quote_id Quote post ID.
	 * @return void
	 */
	public function trigger( $quote_id ) {
		$this->setup_locale();

		$quote_id = absint( $quote_id );
		$customer = (array) get_post_meta( $quote_id, '_wcq_customer', true );

		if ( $quote_id ) {
			$this->object                     = $quote_id;
			$this->placeholders['{quote_id}'] = $quote_id;
		}

		$this->recipient = isset( $customer['email'] ) ? $customer['email'] : '';

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
			'quote'         => (array) get_post_meta( $quote_id, '_wcq_quote', true ),
			'accept_url'    => wcq_get_accept_url( $quote_id ),
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => false,
			'plain_text'    => $plain,
			'email'         => $this,
		);
	}
}
