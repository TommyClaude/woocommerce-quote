/**
 * Mount point for the WooCommerce Quote admin app.
 */
import { createRoot } from '@wordpress/element';

import App from './app';
import './admin.scss';

document.addEventListener( 'DOMContentLoaded', () => {
	const node = document.getElementById( 'wcq-admin-app' );
	if ( node ) {
		createRoot( node ).render( <App /> );
	}
} );
