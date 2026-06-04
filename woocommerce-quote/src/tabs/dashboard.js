/**
 * Dashboard tab: stat cards + 30-day trend chart + recent requests.
 */
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';
import {
	Card,
	CardBody,
	CardHeader,
	Button,
	Spinner,
	Notice,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import StatCard from '../components/stat-card';
import TrendChart from '../components/trend-chart';

const data = window.wcqAdmin || {};

export default function Dashboard() {
	const [ stats, setStats ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( '' );

	useEffect( () => {
		apiFetch( { path: 'wcq/v1/stats' } )
			.then( ( res ) => {
				setStats( res );
				setLoading( false );
			} )
			.catch( () => {
				setError( __( 'Could not load statistics.', 'woocommerce-quote' ) );
				setLoading( false );
			} );
	}, [] );

	if ( loading ) {
		return (
			<div className="wcq-loading">
				<Spinner />
			</div>
		);
	}

	if ( error ) {
		return <Notice status="error" isDismissible={ false }>{ error }</Notice>;
	}

	const t = stats.totals;

	return (
		<div className="wcq-dashboard">
			<div className="wcq-stats-grid">
				<StatCard label={ __( 'Total requests', 'woocommerce-quote' ) } value={ t.total } />
				<StatCard label={ __( 'Pending (New)', 'woocommerce-quote' ) } value={ t.new } accent="new" />
				<StatCard label={ __( 'Quoted', 'woocommerce-quote' ) } value={ t.quoted } accent="quoted" />
				<StatCard label={ __( 'Accepted', 'woocommerce-quote' ) } value={ t.accepted } accent="accepted" />
				<StatCard
					label={ __( 'Conversion rate', 'woocommerce-quote' ) }
					value={ `${ t.conversion }%` }
				/>
			</div>

			<Card className="wcq-card">
				<CardHeader>
					<h2>{ __( 'Requests — last 30 days', 'woocommerce-quote' ) }</h2>
				</CardHeader>
				<CardBody>
					<TrendChart data={ stats.trend } />
				</CardBody>
			</Card>

			<Card className="wcq-card">
				<CardHeader>
					<h2>{ __( 'Recent requests', 'woocommerce-quote' ) }</h2>
					<Button variant="link" href={ stats.list_url || data.listUrl }>
						{ __( 'View all', 'woocommerce-quote' ) }
					</Button>
				</CardHeader>
				<CardBody>
					{ stats.recent.length === 0 ? (
						<p>{ __( 'No quote requests yet.', 'woocommerce-quote' ) }</p>
					) : (
						<table className="wcq-recent">
							<thead>
								<tr>
									<th>{ __( 'Customer', 'woocommerce-quote' ) }</th>
									<th>{ __( 'Status', 'woocommerce-quote' ) }</th>
									<th>{ __( 'Date', 'woocommerce-quote' ) }</th>
									<th />
								</tr>
							</thead>
							<tbody>
								{ stats.recent.map( ( row ) => (
									<tr key={ row.id }>
										<td>
											<strong>{ row.customer || __( '(no name)', 'woocommerce-quote' ) }</strong>
											{ row.email ? (
												<span className="wcq-recent__email">{ row.email }</span>
											) : null }
										</td>
										<td>
											<span className={ `wcq-status-badge wcq-status-${ row.status }` }>
												{ row.status_label }
											</span>
										</td>
										<td>{ row.date }</td>
										<td>
											{ row.edit_link ? (
												<Button variant="secondary" size="small" href={ row.edit_link }>
													{ __( 'Open', 'woocommerce-quote' ) }
												</Button>
											) : null }
										</td>
									</tr>
								) ) }
							</tbody>
						</table>
					) }
				</CardBody>
			</Card>
		</div>
	);
}
