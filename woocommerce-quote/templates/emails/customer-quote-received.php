<?php
/**
 * Customer email: quote request received (HTML).
 *
 * @package WooCommerce_Quote
 *
 * @var int      $quote_id
 * @var array    $customer
 * @var array    $items
 * @var array    $totals
 * @var string   $email_heading
 * @var WC_Email $email
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p>
	<?php
	/* translators: %s: customer first name. */
	printf( esc_html__( 'Hi %s,', 'woocommerce-quote' ), esc_html( isset( $customer['name'] ) ? $customer['name'] : '' ) );
	?>
</p>

<p><?php esc_html_e( 'Thank you for your quote request. We have received it and will get back to you with a price soon.', 'woocommerce-quote' ); ?></p>

<p>
	<?php
	/* translators: %d: quote reference number. */
	printf( esc_html__( 'Your reference number is #%d.', 'woocommerce-quote' ), esc_html( $quote_id ) );
	?>
</p>

<h2><?php esc_html_e( 'Your requested products', 'woocommerce-quote' ); ?></h2>
<?php
wcq_get_template(
	'emails/parts/items-table.php',
	array(
		'items'  => $items,
		'totals' => $totals,
	)
);

do_action( 'woocommerce_email_footer', $email );
