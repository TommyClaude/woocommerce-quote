<?php
/**
 * Customer email: your quote (HTML).
 *
 * @package WooCommerce_Quote
 *
 * @var int      $quote_id
 * @var array    $customer
 * @var array    $items
 * @var array    $quote
 * @var string   $accept_url
 * @var string   $email_heading
 * @var WC_Email $email
 */

defined( 'ABSPATH' ) || exit;

$wcq_prices = isset( $quote['prices'] ) && is_array( $quote['prices'] ) ? $quote['prices'] : array();
$wcq_total  = isset( $quote['total'] ) ? (float) $quote['total'] : 0;
$wcq_note   = isset( $quote['note'] ) ? $quote['note'] : '';

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p>
	<?php
	/* translators: %s: customer name. */
	printf( esc_html__( 'Hi %s,', 'woocommerce-quote' ), esc_html( isset( $customer['name'] ) ? $customer['name'] : '' ) );
	?>
</p>

<p><?php esc_html_e( 'Thank you for your interest. Here is your quote:', 'woocommerce-quote' ); ?></p>

<table cellspacing="0" cellpadding="6" border="1" style="width:100%;border-collapse:collapse;margin:0 0 16px;">
	<thead>
		<tr>
			<th style="text-align:left;"><?php esc_html_e( 'Product', 'woocommerce-quote' ); ?></th>
			<th style="text-align:center;"><?php esc_html_e( 'Qty', 'woocommerce-quote' ); ?></th>
			<th style="text-align:right;"><?php esc_html_e( 'Unit price', 'woocommerce-quote' ); ?></th>
			<th style="text-align:right;"><?php esc_html_e( 'Line total', 'woocommerce-quote' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( (array) $items as $i => $item ) : ?>
			<?php
			$wcq_qty  = isset( $item['quantity'] ) ? (int) $item['quantity'] : 0;
			$wcq_unit = ( isset( $wcq_prices[ $i ] ) && '' !== $wcq_prices[ $i ] ) ? (float) $wcq_prices[ $i ] : (float) ( isset( $item['price'] ) ? $item['price'] : 0 );
			?>
			<tr>
				<td><?php echo esc_html( isset( $item['name'] ) ? $item['name'] : '' ); ?></td>
				<td style="text-align:center;"><?php echo esc_html( $wcq_qty ); ?></td>
				<td style="text-align:right;"><?php echo wp_kses_post( wc_price( $wcq_unit ) ); ?></td>
				<td style="text-align:right;"><?php echo wp_kses_post( wc_price( $wcq_unit * $wcq_qty ) ); ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
	<tfoot>
		<tr>
			<th colspan="3" style="text-align:right;"><?php esc_html_e( 'Total', 'woocommerce-quote' ); ?></th>
			<td style="text-align:right;"><strong><?php echo wp_kses_post( wc_price( $wcq_total ) ); ?></strong></td>
		</tr>
	</tfoot>
</table>

<?php if ( '' !== $wcq_note ) : ?>
	<h2><?php esc_html_e( 'Note', 'woocommerce-quote' ); ?></h2>
	<p><?php echo nl2br( esc_html( $wcq_note ) ); ?></p>
<?php endif; ?>

<?php if ( $accept_url ) : ?>
	<p style="margin:24px 0;">
		<a href="<?php echo esc_url( $accept_url ); ?>" style="background:#7f54b3;color:#fff;text-decoration:none;padding:12px 22px;border-radius:6px;font-weight:600;display:inline-block;">
			<?php esc_html_e( 'Accept quote &amp; checkout', 'woocommerce-quote' ); ?>
		</a>
	</p>
	<p style="font-size:13px;color:#777;">
		<?php esc_html_e( 'Accepting creates an order you can pay for at the standard checkout.', 'woocommerce-quote' ); ?>
	</p>
<?php endif; ?>

<?php
do_action( 'woocommerce_email_footer', $email );
