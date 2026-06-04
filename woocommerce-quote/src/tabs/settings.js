/**
 * Settings tab: every option as a WordPress component, saved over REST.
 */
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';
import {
	Card,
	CardBody,
	RadioControl,
	SelectControl,
	TextControl,
	TextareaControl,
	ToggleControl,
	FormTokenField,
	Button,
	Spinner,
	Notice,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const data = window.wcqAdmin || {};
const options = data.options || {};

/**
 * Map helpers between stored IDs and the labels FormTokenField shows.
 *
 * @param {Array}  list    Option list of { id, name | title }.
 * @return {Object} { labels, idByLabel, labelById }
 */
function buildMaps( list = [] ) {
	const labels = [];
	const idByLabel = {};
	const labelById = {};
	list.forEach( ( item ) => {
		const label = item.name || item.title || String( item.id );
		labels.push( label );
		idByLabel[ label ] = item.id;
		labelById[ item.id ] = label;
	} );
	return { labels, idByLabel, labelById };
}

const catMap = buildMaps( options.categories );
const productMap = buildMaps( options.products );
const roleMap = buildMaps( options.roles );

export default function Settings() {
	const [ form, setForm ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ notice, setNotice ] = useState( null );

	useEffect( () => {
		apiFetch( { path: 'wcq/v1/settings' } )
			.then( ( res ) => {
				setForm( res );
				setLoading( false );
			} )
			.catch( () => {
				setNotice( { status: 'error', text: __( 'Could not load settings.', 'woocommerce-quote' ) } );
				setLoading( false );
			} );
	}, [] );

	const update = ( key, value ) => setForm( ( prev ) => ( { ...prev, [ key ]: value } ) );

	const tokensToIds = ( tokens, map ) =>
		tokens.map( ( token ) => map.idByLabel[ token ] ).filter( ( id ) => id !== undefined );

	const idsToTokens = ( ids, map ) =>
		( ids || [] ).map( ( id ) => map.labelById[ id ] ).filter( Boolean );

	const save = () => {
		setSaving( true );
		setNotice( null );
		apiFetch( {
			path: 'wcq/v1/settings',
			method: 'POST',
			data: form,
		} )
			.then( ( res ) => {
				setForm( res.settings );
				setSaving( false );
				setNotice( { status: 'success', text: __( 'Settings saved.', 'woocommerce-quote' ) } );
			} )
			.catch( () => {
				setSaving( false );
				setNotice( { status: 'error', text: __( 'Could not save settings.', 'woocommerce-quote' ) } );
			} );
	};

	if ( loading ) {
		return (
			<div className="wcq-loading">
				<Spinner />
			</div>
		);
	}

	const pageChoices = [
		{ label: __( '— Select a page —', 'woocommerce-quote' ), value: '0' },
		...( options.pages || [] ).map( ( p ) => ( {
			label: p.title || `#${ p.id }`,
			value: String( p.id ),
		} ) ),
	];

	return (
		<div className="wcq-settings">
			{ notice ? (
				<Notice status={ notice.status } onRemove={ () => setNotice( null ) }>
					{ notice.text }
				</Notice>
			) : null }

			<Card className="wcq-card">
				<CardBody>
					<RadioControl
						label={ __( 'Button mode', 'woocommerce-quote' ) }
						help={ __( 'Show the Add to Quote button alongside Add to Cart, or replace it.', 'woocommerce-quote' ) }
						selected={ form.button_mode }
						options={ [
							{ label: __( 'Show both buttons', 'woocommerce-quote' ), value: 'both' },
							{ label: __( 'Replace Add to Cart', 'woocommerce-quote' ), value: 'replace' },
						] }
						onChange={ ( value ) => update( 'button_mode', value ) }
					/>
				</CardBody>
			</Card>

			<Card className="wcq-card">
				<CardBody>
					<SelectControl
						label={ __( 'RFQ scope', 'woocommerce-quote' ) }
						help={ __( 'Which products show the Add to Quote button.', 'woocommerce-quote' ) }
						value={ form.rfq_scope }
						options={ [
							{ label: __( 'All products', 'woocommerce-quote' ), value: 'all' },
							{ label: __( 'Selected categories', 'woocommerce-quote' ), value: 'categories' },
							{ label: __( 'Selected products', 'woocommerce-quote' ), value: 'products' },
							{ label: __( 'Hidden-price products only', 'woocommerce-quote' ), value: 'hidden_price' },
							{ label: __( 'Selected user roles', 'woocommerce-quote' ), value: 'roles' },
						] }
						onChange={ ( value ) => update( 'rfq_scope', value ) }
					/>

					{ form.rfq_scope === 'categories' ? (
						<FormTokenField
							label={ __( 'Categories', 'woocommerce-quote' ) }
							value={ idsToTokens( form.scope_categories, catMap ) }
							suggestions={ catMap.labels }
							onChange={ ( tokens ) => update( 'scope_categories', tokensToIds( tokens, catMap ) ) }
							__experimentalExpandOnFocus
						/>
					) : null }

					{ form.rfq_scope === 'products' ? (
						<FormTokenField
							label={ __( 'Products', 'woocommerce-quote' ) }
							value={ idsToTokens( form.scope_products, productMap ) }
							suggestions={ productMap.labels }
							onChange={ ( tokens ) => update( 'scope_products', tokensToIds( tokens, productMap ) ) }
							__experimentalExpandOnFocus
						/>
					) : null }

					{ form.rfq_scope === 'roles' ? (
						<FormTokenField
							label={ __( 'User roles', 'woocommerce-quote' ) }
							value={ idsToTokens( form.scope_roles, roleMap ) }
							suggestions={ roleMap.labels }
							onChange={ ( tokens ) => update( 'scope_roles', tokensToIds( tokens, roleMap ) ) }
							__experimentalExpandOnFocus
						/>
					) : null }
				</CardBody>
			</Card>

			<Card className="wcq-card">
				<CardBody>
					<RadioControl
						label={ __( 'Access', 'woocommerce-quote' ) }
						selected={ form.access }
						options={ [
							{ label: __( 'Allow guests', 'woocommerce-quote' ), value: 'guests' },
							{ label: __( 'Require login', 'woocommerce-quote' ), value: 'login' },
						] }
						onChange={ ( value ) => update( 'access', value ) }
					/>

					<ToggleControl
						label={ __( 'Hide price & Add to Cart for quotable products', 'woocommerce-quote' ) }
						help={ __( 'Show a "Price on request" label and only the quote button for products in the RFQ scope.', 'woocommerce-quote' ) }
						checked={ !! form.hide_price }
						onChange={ ( value ) => update( 'hide_price', value ) }
					/>

					{ form.hide_price ? (
						<TextControl
							label={ __( 'Hidden price label', 'woocommerce-quote' ) }
							value={ form.hidden_price_label }
							placeholder={ __( 'Price on request', 'woocommerce-quote' ) }
							onChange={ ( value ) => update( 'hidden_price_label', value ) }
						/>
					) : null }
				</CardBody>
			</Card>

			<Card className="wcq-card">
				<CardBody>
					<TextControl
						label={ __( 'Button label', 'woocommerce-quote' ) }
						value={ form.button_label }
						placeholder={ __( 'Add to Quote', 'woocommerce-quote' ) }
						onChange={ ( value ) => update( 'button_label', value ) }
					/>

					<TextareaControl
						label={ __( 'Recipient email(s)', 'woocommerce-quote' ) }
						help={ __( 'Comma-separated. New requests are emailed here.', 'woocommerce-quote' ) }
						value={ form.recipient_emails }
						onChange={ ( value ) => update( 'recipient_emails', value ) }
					/>

					<SelectControl
						label={ __( 'Quote list page', 'woocommerce-quote' ) }
						help={ __( 'The page that contains the [woocommerce_quote] shortcode.', 'woocommerce-quote' ) }
						value={ String( form.quote_page_id || 0 ) }
						options={ pageChoices }
						onChange={ ( value ) => update( 'quote_page_id', parseInt( value, 10 ) ) }
					/>

					<ToggleControl
						label={ __( 'Delete all plugin data when uninstalling', 'woocommerce-quote' ) }
						checked={ !! form.remove_data_on_uninstall }
						onChange={ ( value ) => update( 'remove_data_on_uninstall', value ) }
					/>
				</CardBody>
			</Card>

			<div className="wcq-settings__save">
				<Button variant="primary" isBusy={ saving } disabled={ saving } onClick={ save }>
					{ saving ? __( 'Saving…', 'woocommerce-quote' ) : __( 'Save settings', 'woocommerce-quote' ) }
				</Button>
			</div>
		</div>
	);
}
