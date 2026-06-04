<?php
/**
 * Admin email: new quote request (HTML).
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

<p><?php esc_html_e( 'A customer has submitted a new quote request.', 'woocommerce-quote' ); ?></p>

<?php
$wcq_edit_link = function_exists( 'get_edit_post_link' ) ? get_edit_post_link( $quote_id ) : '';
if ( $wcq_edit_link ) {
	echo '<p><a href="' . esc_url( $wcq_edit_link ) . '">' . esc_html__( 'Open this request in your dashboard', 'woocommerce-quote' ) . '</a></p>';
}
?>

<h2><?php esc_html_e( 'Customer', 'woocommerce-quote' ); ?></h2>
<ul>
	<li><strong><?php esc_html_e( 'Name', 'woocommerce-quote' ); ?>:</strong> <?php echo esc_html( isset( $customer['name'] ) ? $customer['name'] : '' ); ?></li>
	<li><strong><?php esc_html_e( 'Email', 'woocommerce-quote' ); ?>:</strong> <?php echo esc_html( isset( $customer['email'] ) ? $customer['email'] : '' ); ?></li>
	<?php if ( ! empty( $customer['phone'] ) ) : ?>
		<li><strong><?php esc_html_e( 'Phone', 'woocommerce-quote' ); ?>:</strong> <?php echo esc_html( $customer['phone'] ); ?></li>
	<?php endif; ?>
	<?php if ( ! empty( $customer['company'] ) ) : ?>
		<li><strong><?php esc_html_e( 'Company', 'woocommerce-quote' ); ?>:</strong> <?php echo esc_html( $customer['company'] ); ?></li>
	<?php endif; ?>
</ul>

<?php if ( ! empty( $customer['message'] ) ) : ?>
	<h2><?php esc_html_e( 'Message', 'woocommerce-quote' ); ?></h2>
	<p><?php echo nl2br( esc_html( $customer['message'] ) ); ?></p>
<?php endif; ?>

<h2><?php esc_html_e( 'Requested products', 'woocommerce-quote' ); ?></h2>
<?php
wcq_get_template(
	'emails/parts/items-table.php',
	array(
		'items'  => $items,
		'totals' => $totals,
	)
);

do_action( 'woocommerce_email_footer', $email );
