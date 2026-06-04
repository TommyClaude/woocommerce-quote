/**
 * Brand mark (custom SVG — WordPress has no logo component).
 */
export default function Logo() {
	return (
		<svg
			className="wcq-logo"
			width="44"
			height="44"
			viewBox="0 0 44 44"
			role="img"
			aria-hidden="true"
			focusable="false"
			xmlns="http://www.w3.org/2000/svg"
		>
			<defs>
				<linearGradient id="wcq-logo-grad" x1="0" y1="0" x2="1" y2="1">
					<stop offset="0" stopColor="#7f54b3" />
					<stop offset="1" stopColor="#9b6fd4" />
				</linearGradient>
			</defs>
			<rect width="44" height="44" rx="10" fill="url(#wcq-logo-grad)" />
			<path
				d="M11 14a3 3 0 0 1 3-3h16a3 3 0 0 1 3 3v10a3 3 0 0 1-3 3H19l-6 5v-5h-1a3 3 0 0 1-3-3V14z"
				fill="#fff"
				opacity="0.95"
			/>
			<text
				x="22"
				y="23"
				textAnchor="middle"
				fontFamily="Georgia, 'Times New Roman', serif"
				fontSize="15"
				fontWeight="700"
				fill="#7f54b3"
			>
				$
			</text>
		</svg>
	);
}
