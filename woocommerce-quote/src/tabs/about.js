/**
 * About us tab: short blurb + "more plugins" cross-sell cards.
 */
import { Card, CardBody, CardHeader, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const data = window.wcqAdmin || {};

const MORE_PLUGINS = [
	{
		title: __( 'WP Image Preview', 'woocommerce-quote' ),
		text: __( 'Instant, shareable previews for your media — generate and link in one click.', 'woocommerce-quote' ),
		url: 'https://github.com/TommyClaude/wpimage-preview',
	},
];

export default function About() {
	return (
		<div className="wcq-about">
			<Card className="wcq-card">
				<CardHeader>
					<h2>{ __( 'About this plugin', 'woocommerce-quote' ) }</h2>
				</CardHeader>
				<CardBody>
					<p>
						{ __(
							'Request a Quote for WooCommerce lets your customers collect products into a quote list and ask for a price instead of buying immediately. Perfect for B2B, wholesale, hidden-price and made-to-order catalogs.',
							'woocommerce-quote'
						) }
					</p>
					<p>
						{ __(
							'It is fully translation-ready and driven by admin settings, so you control the button mode, which products are quotable, and whether guests can request quotes.',
							'woocommerce-quote'
						) }
					</p>
					<p>
						<Button variant="secondary" href={ data.supportUrl || '#' } target="_blank" rel="noreferrer noopener">
							{ __( 'Get support', 'woocommerce-quote' ) }
						</Button>
					</p>
				</CardBody>
			</Card>

			<h2 className="wcq-about__heading">{ __( 'More from us', 'woocommerce-quote' ) }</h2>
			<div className="wcq-about__grid">
				{ MORE_PLUGINS.map( ( plugin ) => (
					<Card key={ plugin.title } className="wcq-card">
						<CardHeader>
							<h3>{ plugin.title }</h3>
						</CardHeader>
						<CardBody>
							<p>{ plugin.text }</p>
							<Button variant="link" href={ plugin.url } target="_blank" rel="noreferrer noopener">
								{ __( 'Learn more', 'woocommerce-quote' ) }
							</Button>
						</CardBody>
					</Card>
				) ) }
			</div>
		</div>
	);
}
