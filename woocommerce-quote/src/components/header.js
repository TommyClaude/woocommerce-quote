/**
 * App header: logo + name + version + tagline, with a Support button.
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import Logo from './logo';

const data = window.wcqAdmin || {};

export default function Header() {
	return (
		<header className="wcq-header">
			<div className="wcq-header__brand">
				<Logo />
				<div className="wcq-header__title">
					<h1>{ __( 'WooCommerce Quote', 'woocommerce-quote' ) }</h1>
					<p className="wcq-header__tagline">
						{ __( 'Request a Quote for WooCommerce', 'woocommerce-quote' ) }
						{ data.version ? ` · v${ data.version }` : '' }
					</p>
				</div>
			</div>
			<div className="wcq-header__actions">
				<Button
					variant="secondary"
					href={ data.supportUrl || '#' }
					target="_blank"
					rel="noreferrer noopener"
				>
					{ __( 'Support', 'woocommerce-quote' ) }
				</Button>
			</div>
		</header>
	);
}
