/**
 * App shell: Header + tabbed interface (Dashboard / Settings / About us).
 */
import { TabPanel } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import Header from './components/header';
import Dashboard from './tabs/dashboard';
import Settings from './tabs/settings';
import About from './tabs/about';

const TABS = [
	{ name: 'dashboard', title: __( 'Dashboard', 'woocommerce-quote' ) },
	{ name: 'settings', title: __( 'Settings', 'woocommerce-quote' ) },
	{ name: 'about', title: __( 'About us', 'woocommerce-quote' ) },
];

function renderTab( tab ) {
	switch ( tab.name ) {
		case 'settings':
			return <Settings />;
		case 'about':
			return <About />;
		case 'dashboard':
		default:
			return <Dashboard />;
	}
}

export default function App() {
	return (
		<div className="wcq-app">
			<Header />
			<div className="wcq-app__body">
				<TabPanel className="wcq-tabs" activeClass="is-active" tabs={ TABS }>
					{ ( tab ) => (
						<div className="wcq-tab-content">{ renderTab( tab ) }</div>
					) }
				</TabPanel>
			</div>
		</div>
	);
}
