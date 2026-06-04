<?php
/**
 * Email loader: registers the WooCommerce email classes and fires the
 * notification when a quote request is created.
 *
 * @package WooCommerce_Quote
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WCQ_Emails
 */
class WCQ_Emails {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_email_classes', array( $this, 'register_emails' ) );
		add_action( 'wcq_quote_created', array( $this, 'on_quote_created' ), 10, 3 );
	}

	/**
	 * Register our WC_Email subclasses with WooCommerce.
	 *
	 * Loaded lazily here (when the mailer initialises) so `WC_Email` is sure to
	 * exist before our subclasses are declared.
	 *
	 * @param array $emails Registered email classes.
	 * @return array
	 */
	public function register_emails( $emails ) {
		require_once WCQ_PLUGIN_DIR . 'includes/emails/class-wcq-email-admin-new-quote.php';
		require_once WCQ_PLUGIN_DIR . 'includes/emails/class-wcq-email-customer-quote-received.php';
		require_once WCQ_PLUGIN_DIR . 'includes/emails/class-wcq-email-customer-quote.php';

		$emails['WCQ_Email_Admin_New_Quote']         = new WCQ_Email_Admin_New_Quote();
		$emails['WCQ_Email_Customer_Quote_Received'] = new WCQ_Email_Customer_Quote_Received();
		$emails['WCQ_Email_Customer_Quote']         = new WCQ_Email_Customer_Quote();

		return $emails;
	}

	/**
	 * Trigger the email notifications for a new quote.
	 *
	 * Loads the mailer (which constructs the email objects and attaches their
	 * triggers) before firing the notification hook.
	 *
	 * @param int   $quote_id Quote post ID.
	 * @param array $customer Customer fields.
	 * @param array $items    Line items.
	 * @return void
	 */
	public function on_quote_created( $quote_id, $customer, $items ) {
		if ( function_exists( 'WC' ) ) {
			WC()->mailer();
		}

		do_action( 'wcq_quote_created_notification', $quote_id, $customer, $items );
	}
}
