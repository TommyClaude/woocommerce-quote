<?php
/**
 * Minimal POT generator for the plugin (no WP-CLI required).
 *
 * Scans PHP (gettext calls) and the src/*.js admin app (@wordpress/i18n calls)
 * and writes languages/woocommerce-quote.pot.
 *
 * Usage: php bin/make-pot.php
 *
 * @package WooCommerce_Quote
 */

$root   = dirname( __DIR__ );
$domain = 'woocommerce-quote';
$out    = $root . '/languages/woocommerce-quote.pot';

$entries = array(); // key => [ 'msgid','plural','context','refs'=>[] ].

/**
 * Register a string.
 *
 * @param array  $entries Entries (by ref).
 * @param string $msgid   Singular.
 * @param string $plural  Plural or ''.
 * @param string $context Context or ''.
 * @param string $ref     file:line.
 */
function wcq_add( array &$entries, $msgid, $plural, $context, $ref ) {
	global $domain;
	if ( '' === $msgid || $domain === $msgid ) {
		return;
	}
	$key = $context . "\x04" . $msgid . "\x04" . $plural;
	if ( ! isset( $entries[ $key ] ) ) {
		$entries[ $key ] = array(
			'msgid'   => $msgid,
			'plural'  => $plural,
			'context' => $context,
			'refs'    => array(),
		);
	}
	$entries[ $key ]['refs'][ $ref ] = true;
}

/**
 * Collect files by extension.
 *
 * @param string $dir Base dir.
 * @param array  $ext Extensions.
 * @return array
 */
function wcq_files( $dir, array $ext ) {
	$out = array();
	$it  = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) );
	foreach ( $it as $file ) {
		$path = $file->getPathname();
		if ( strpos( $path, '/node_modules/' ) !== false || strpos( $path, '/build/' ) !== false || strpos( $path, '/vendor/' ) !== false ) {
			continue;
		}
		if ( in_array( strtolower( $file->getExtension() ), $ext, true ) ) {
			$out[] = $path;
		}
	}
	sort( $out );
	return $out;
}

// Index in the collected string-literal list: msgid, plural, context.
$functions = array(
	'__'         => array( 'msgid' => 0 ),
	'_e'         => array( 'msgid' => 0 ),
	'esc_html__' => array( 'msgid' => 0 ),
	'esc_html_e' => array( 'msgid' => 0 ),
	'esc_attr__' => array( 'msgid' => 0 ),
	'esc_attr_e' => array( 'msgid' => 0 ),
	'_x'         => array( 'msgid' => 0, 'context' => 1 ),
	'esc_html_x' => array( 'msgid' => 0, 'context' => 1 ),
	'esc_attr_x' => array( 'msgid' => 0, 'context' => 1 ),
	'_n'         => array( 'msgid' => 0, 'plural' => 1 ),
	'_n_noop'    => array( 'msgid' => 0, 'plural' => 1 ),
	'_nx'        => array( 'msgid' => 0, 'plural' => 1, 'context' => 2 ),
	'_nx_noop'   => array( 'msgid' => 0, 'plural' => 1, 'context' => 2 ),
);

// --- PHP extraction via tokenizer ---
foreach ( wcq_files( $root, array( 'php' ) ) as $file ) {
	if ( $file === __FILE__ ) {
		continue;
	}
	$rel    = ltrim( str_replace( $root, '', $file ), '/' );
	$tokens = token_get_all( file_get_contents( $file ) );
	$count  = count( $tokens );

	for ( $i = 0; $i < $count; $i++ ) {
		$tok = $tokens[ $i ];
		if ( ! is_array( $tok ) || T_STRING !== $tok[0] || ! isset( $functions[ $tok[1] ] ) ) {
			continue;
		}
		$fn   = $tok[1];
		$line = $tok[2];

		// Next non-whitespace must be "(".
		$j = $i + 1;
		while ( $j < $count && is_array( $tokens[ $j ] ) && T_WHITESPACE === $tokens[ $j ][0] ) {
			$j++;
		}
		if ( $j >= $count || '(' !== $tokens[ $j ] ) {
			continue;
		}

		// Walk the argument list, collecting standalone string literals.
		$depth   = 0;
		$strings = array();
		for ( $k = $j; $k < $count; $k++ ) {
			$t = $tokens[ $k ];
			if ( '(' === $t ) {
				$depth++;
				continue;
			}
			if ( ')' === $t ) {
				$depth--;
				if ( 0 === $depth ) {
					break;
				}
				continue;
			}
			if ( 1 === $depth && is_array( $t ) && T_CONSTANT_ENCAPSED_STRING === $t[0] ) {
				// Skip string fragments that are part of a concatenation.
				$prev = $k - 1;
				while ( $prev > $j && is_array( $tokens[ $prev ] ) && T_WHITESPACE === $tokens[ $prev ][0] ) {
					$prev--;
				}
				$next = $k + 1;
				while ( $next < $count && is_array( $tokens[ $next ] ) && T_WHITESPACE === $tokens[ $next ][0] ) {
					$next++;
				}
				if ( '.' === $tokens[ $prev ] || ( isset( $tokens[ $next ] ) && '.' === $tokens[ $next ] ) ) {
					continue;
				}
				$strings[] = wcq_unquote( $t[1] );
			}
		}

		$map     = $functions[ $fn ];
		$msgid   = isset( $map['msgid'], $strings[ $map['msgid'] ] ) ? $strings[ $map['msgid'] ] : '';
		$plural  = isset( $map['plural'], $strings[ $map['plural'] ] ) ? $strings[ $map['plural'] ] : '';
		$context = isset( $map['context'], $strings[ $map['context'] ] ) ? $strings[ $map['context'] ] : '';
		wcq_add( $entries, $msgid, $plural, $context, "$rel:$line" );
	}
}

// --- JS extraction via regex ---
foreach ( wcq_files( $root . '/src', array( 'js' ) ) as $file ) {
	$rel  = ltrim( str_replace( $root, '', $file ), '/' );
	$code = file_get_contents( $file );

	$patterns = array(
		// _n( 'single', 'plural', ...
		array( '/\b_n\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1\s*,\s*([\'"])((?:\\\\.|(?!\3).)*)\3/s', 'n' ),
		// _x( 'text', 'context'
		array( '/\b_x\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1\s*,\s*([\'"])((?:\\\\.|(?!\3).)*)\3/s', 'x' ),
		// __( 'text'
		array( '/\b__\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1/s', 's' ),
	);

	foreach ( $patterns as $p ) {
		if ( preg_match_all( $p[0], $code, $m, PREG_OFFSET_CAPTURE ) ) {
			foreach ( $m[2] as $idx => $hit ) {
				$line   = substr_count( substr( $code, 0, $hit[1] ), "\n" ) + 1;
				$msgid  = wcq_js_unescape( $hit[0] );
				$plural = '';
				$ctx    = '';
				if ( 'n' === $p[1] ) {
					$plural = wcq_js_unescape( $m[4][ $idx ][0] );
				} elseif ( 'x' === $p[1] ) {
					$ctx = wcq_js_unescape( $m[4][ $idx ][0] );
				}
				wcq_add( $entries, $msgid, $plural, $ctx, "$rel:$line" );
			}
		}
	}
}

/**
 * Turn a PHP string literal token into its value.
 *
 * @param string $raw Raw token text including quotes.
 * @return string
 */
function wcq_unquote( $raw ) {
	$q = $raw[0];
	$s = substr( $raw, 1, -1 );
	if ( "'" === $q ) {
		return str_replace( array( "\\'", '\\\\' ), array( "'", '\\' ), $s );
	}
	return stripcslashes( $s );
}

/**
 * Unescape a JS single/double-quoted string body.
 *
 * @param string $s String body.
 * @return string
 */
function wcq_js_unescape( $s ) {
	return stripcslashes( $s );
}

/**
 * Escape a value for a PO msgid/msgstr.
 *
 * @param string $s Value.
 * @return string
 */
function wcq_po( $s ) {
	$s = str_replace( array( '\\', '"', "\t", "\n" ), array( '\\\\', '\\"', '\\t', '\\n' ), $s );
	return $s;
}

// --- Write the POT ---
ksort( $entries );

$now    = gmdate( 'Y-m-d H:iO' );
$header = <<<POT
# Copyright (C) TommyClaude
# This file is distributed under the GPL-2.0-or-later license.
msgid ""
msgstr ""
"Project-Id-Version: Request a Quote for WooCommerce\\n"
"Report-Msgid-Bugs-To: https://github.com/TommyClaude/woocommerce-quote\\n"
"POT-Creation-Date: $now\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Language-Team: LANGUAGE\\n"
"X-Domain: woocommerce-quote\\n"

POT;

$body = '';
foreach ( $entries as $e ) {
	$refs = implode( ' ', array_keys( $e['refs'] ) );
	$body .= "#: $refs\n";
	if ( '' !== $e['context'] ) {
		$body .= 'msgctxt "' . wcq_po( $e['context'] ) . "\"\n";
	}
	$body .= 'msgid "' . wcq_po( $e['msgid'] ) . "\"\n";
	if ( '' !== $e['plural'] ) {
		$body .= 'msgid_plural "' . wcq_po( $e['plural'] ) . "\"\n";
		$body .= "msgstr[0] \"\"\n";
		$body .= "msgstr[1] \"\"\n";
	} else {
		$body .= "msgstr \"\"\n";
	}
	$body .= "\n";
}

if ( ! is_dir( dirname( $out ) ) ) {
	mkdir( dirname( $out ), 0775, true );
}
file_put_contents( $out, $header . $body );

echo 'Wrote ' . count( $entries ) . ' strings to ' . str_replace( $root . '/', '', $out ) . "\n";
