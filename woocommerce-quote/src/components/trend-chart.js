/**
 * 30-day trend chart (custom SVG — WordPress has no chart component).
 *
 * @param {Object}   props
 * @param {Array}    props.data Array of { date, label, count }.
 */
import { __, sprintf } from '@wordpress/i18n';

const WIDTH = 640;
const HEIGHT = 180;
const PAD = 24;

export default function TrendChart( { data = [] } ) {
	if ( ! data.length ) {
		return null;
	}

	const counts = data.map( ( d ) => d.count );
	const max = Math.max( 1, ...counts );
	const innerW = WIDTH - PAD * 2;
	const innerH = HEIGHT - PAD * 2;
	const step = data.length > 1 ? innerW / ( data.length - 1 ) : 0;

	const points = data.map( ( d, i ) => {
		const x = PAD + step * i;
		const y = PAD + innerH - ( d.count / max ) * innerH;
		return { x, y, ...d };
	} );

	const line = points.map( ( p ) => `${ p.x },${ p.y }` ).join( ' ' );
	const area =
		`${ PAD },${ PAD + innerH } ` +
		line +
		` ${ PAD + step * ( data.length - 1 ) },${ PAD + innerH }`;

	return (
		<div className="wcq-chart">
			<svg
				viewBox={ `0 0 ${ WIDTH } ${ HEIGHT }` }
				preserveAspectRatio="none"
				role="img"
				aria-label={ __( 'Quote requests over the last 30 days', 'woocommerce-quote' ) }
			>
				<defs>
					<linearGradient id="wcq-area" x1="0" y1="0" x2="0" y2="1">
						<stop offset="0" stopColor="#7f54b3" stopOpacity="0.30" />
						<stop offset="1" stopColor="#7f54b3" stopOpacity="0" />
					</linearGradient>
				</defs>

				{ /* baseline */ }
				<line
					x1={ PAD }
					y1={ PAD + innerH }
					x2={ WIDTH - PAD }
					y2={ PAD + innerH }
					stroke="#e0e0e0"
					strokeWidth="1"
				/>

				<polygon points={ area } fill="url(#wcq-area)" />
				<polyline
					points={ line }
					fill="none"
					stroke="#7f54b3"
					strokeWidth="2"
					strokeLinejoin="round"
					strokeLinecap="round"
				/>

				{ points.map( ( p, i ) => (
					<circle key={ i } cx={ p.x } cy={ p.y } r="2.5" fill="#7f54b3">
						<title>
							{ sprintf(
								/* translators: 1: date, 2: number of requests. */
								__( '%1$s: %2$d requests', 'woocommerce-quote' ),
								p.label,
								p.count
							) }
						</title>
					</circle>
				) ) }
			</svg>
		</div>
	);
}
