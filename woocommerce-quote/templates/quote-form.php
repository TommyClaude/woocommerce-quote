<?php
/**
 * Quote request form.
 *
 * Override by copying to your-theme/woocommerce-quote/quote-form.php
 *
 * @package WooCommerce_Quote
 */

defined( 'ABSPATH' ) || exit;

// Re-populate fields if validation sent the user back to the form.
// phpcs:disable WordPress.Security.NonceVerification.Missing -- repopulation only; the submit handler verifies the nonce.
$wcq_old = array(
	'name'    => isset( $_POST['wcq_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wcq_name'] ) ) : '',
	'email'   => isset( $_POST['wcq_email'] ) ? sanitize_email( wp_unslash( $_POST['wcq_email'] ) ) : '',
	'phone'   => isset( $_POST['wcq_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['wcq_phone'] ) ) : '',
	'company' => isset( $_POST['wcq_company'] ) ? sanitize_text_field( wp_unslash( $_POST['wcq_company'] ) ) : '',
	'message' => isset( $_POST['wcq_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['wcq_message'] ) ) : '',
);
// phpcs:enable WordPress.Security.NonceVerification.Missing
?>
<form class="wcq-form" method="post" action="">
	<h3 class="wcq-form-title"><?php esc_html_e( 'Request a quote', 'woocommerce-quote' ); ?></h3>

	<?php wp_nonce_field( 'wcq_submit_quote', 'wcq_nonce' ); ?>
	<input type="hidden" name="wcq_action" value="submit_quote" />

	<p class="wcq-hp" aria-hidden="true">
		<label>
			<?php esc_html_e( 'Leave this field empty', 'woocommerce-quote' ); ?>
			<input type="text" name="wcq_website" value="" tabindex="-1" autocomplete="off" />
		</label>
	</p>

	<p class="wcq-field wcq-field-name">
		<label for="wcq_name"><?php esc_html_e( 'Name', 'woocommerce-quote' ); ?> <span class="required">*</span></label>
		<input type="text" id="wcq_name" name="wcq_name" required value="<?php echo esc_attr( $wcq_old['name'] ); ?>" />
	</p>

	<p class="wcq-field wcq-field-email">
		<label for="wcq_email"><?php esc_html_e( 'Email', 'woocommerce-quote' ); ?> <span class="required">*</span></label>
		<input type="email" id="wcq_email" name="wcq_email" required value="<?php echo esc_attr( $wcq_old['email'] ); ?>" />
	</p>

	<p class="wcq-field wcq-field-phone">
		<label for="wcq_phone"><?php esc_html_e( 'Phone', 'woocommerce-quote' ); ?></label>
		<input type="tel" id="wcq_phone" name="wcq_phone" value="<?php echo esc_attr( $wcq_old['phone'] ); ?>" />
	</p>

	<p class="wcq-field wcq-field-company">
		<label for="wcq_company"><?php esc_html_e( 'Company', 'woocommerce-quote' ); ?></label>
		<input type="text" id="wcq_company" name="wcq_company" value="<?php echo esc_attr( $wcq_old['company'] ); ?>" />
	</p>

	<p class="wcq-field wcq-field-message">
		<label for="wcq_message"><?php esc_html_e( 'Message', 'woocommerce-quote' ); ?></label>
		<textarea id="wcq_message" name="wcq_message" rows="4"><?php echo esc_textarea( $wcq_old['message'] ); ?></textarea>
	</p>

	<p class="wcq-field wcq-field-submit">
		<button type="submit" class="button wcq-submit"><?php esc_html_e( 'Submit request', 'woocommerce-quote' ); ?></button>
	</p>
</form>
