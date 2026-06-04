/**
 * A single dashboard statistic card.
 */
import { Card, CardBody } from '@wordpress/components';

export default function StatCard( { label, value, accent } ) {
	const className = accent ? `wcq-stat is-${ accent }` : 'wcq-stat';
	return (
		<Card className={ className } size="small">
			<CardBody>
				<div className="wcq-stat__value">{ value }</div>
				<div className="wcq-stat__label">{ label }</div>
			</CardBody>
		</Card>
	);
}
