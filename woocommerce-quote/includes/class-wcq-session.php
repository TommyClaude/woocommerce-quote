<?php
/**
 * The customer's quote list, stored in the WooCommerce session (guests OK).
 *
 * @package WooCommerce_Quote
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WCQ_Session
 */
class WCQ_Session {

	/**
	 * Session key holding the raw items array.
	 *
	 * @var string
	 */
	const KEY = 'wcq_items';

	/**
	 * The WooCommerce session handler, or null if unavailable.
	 *
	 * @return WC_Session|null
	 */
	protected function session() {
		if ( function_exists( 'WC' ) && WC()->session ) {
			return WC()->session;
		}
		return null;
	}

	/**
	 * Build the storage key for a product / variation pair.
	 *
	 * @param int $product_id   Product ID.
	 * @param int $variation_id Variation ID (0 if none).
	 * @return string
	 */
	public function item_key( $product_id, $variation_id = 0 ) {
		return md5( absint( $product_id ) . '-' . absint( $variation_id ) );
	}

	/**
	 * Raw items as stored: key => [ product_id, variation_id, quantity ].
	 *
	 * @return array<string,array>
	 */
	public function get_items() {
		$session = $this->session();
		if ( ! $session ) {
			return array();
		}
		$items = $session->get( self::KEY, array() );
		return is_array( $items ) ? $items : array();
	}

	/**
	 * Persist the raw items array, starting a guest session cookie if needed.
	 *
	 * @param array $items Raw items.
	 * @return void
	 */
	protected function save( array $items ) {
		$session = $this->session();
		if ( ! $session ) {
			return;
		}
		if ( ! $session->has_session() ) {
			$session->set_customer_session_cookie( true );
		}
		$session->set( self::KEY, $items );
	}

	/**
	 * Add (or increment) a product in the quote list.
	 *
	 * @param int $product_id   Product ID.
	 * @param int $quantity     Quantity to add.
	 * @param int $variation_id Variation ID (0 if none).
	 * @return string The item key.
	 */
	public function add( $product_id, $quantity = 1, $variation_id = 0 ) {
		$product_id   = absint( $product_id );
		$variation_id = absint( $variation_id );
		$quantity     = max( 1, absint( $quantity ) );
		$key          = $this->item_key( $product_id, $variation_id );

		$items = $this->get_items();
		if ( isset( $items[ $key ] ) ) {
			$items[ $key ]['quantity'] += $quantity;
		} else {
			$items[ $key ] = array(
				'product_id'   => $product_id,
				'variation_id' => $variation_id,
				'quantity'     => $quantity,
			);
		}

		$this->save( $items );
		return $key;
	}

	/**
	 * Set the quantity of an item (removing it when quantity < 1).
	 *
	 * @param string $key      Item key.
	 * @param int    $quantity New quantity.
	 * @return bool Whether the item existed.
	 */
	public function update( $key, $quantity ) {
		$items = $this->get_items();
		if ( ! isset( $items[ $key ] ) ) {
			return false;
		}
		$quantity = absint( $quantity );
		if ( $quantity < 1 ) {
			unset( $items[ $key ] );
		} else {
			$items[ $key ]['quantity'] = $quantity;
		}
		$this->save( $items );
		return true;
	}

	/**
	 * Remove an item.
	 *
	 * @param string $key Item key.
	 * @return bool Whether the item existed.
	 */
	public function remove( $key ) {
		$items = $this->get_items();
		if ( ! isset( $items[ $key ] ) ) {
			return false;
		}
		unset( $items[ $key ] );
		$this->save( $items );
		return true;
	}

	/**
	 * Empty the quote list.
	 *
	 * @return void
	 */
	public function clear() {
		$this->save( array() );
	}

	/**
	 * Total quantity across all items.
	 *
	 * @return int
	 */
	public function count() {
		$count = 0;
		foreach ( $this->get_items() as $item ) {
			$count += (int) $item['quantity'];
		}
		return $count;
	}

	/**
	 * Whether the quote list has no items.
	 *
	 * @return bool
	 */
	public function is_empty() {
		return 0 === count( $this->get_items() );
	}

	/**
	 * Resolved contents with product objects, skipping items whose product no
	 * longer exists.
	 *
	 * @return array<string,array> key => [ key, product, product_id, variation_id, quantity ]
	 */
	public function get_contents() {
		$contents = array();
		foreach ( $this->get_items() as $key => $item ) {
			$lookup  = $item['variation_id'] ? $item['variation_id'] : $item['product_id'];
			$product = wc_get_product( $lookup );
			if ( ! ( $product instanceof WC_Product ) ) {
				continue;
			}
			$contents[ $key ] = array(
				'key'          => $key,
				'product'      => $product,
				'product_id'   => (int) $item['product_id'],
				'variation_id' => (int) $item['variation_id'],
				'quantity'     => (int) $item['quantity'],
			);
		}
		return $contents;
	}

	/**
	 * Snapshot totals based on current prices.
	 *
	 * @return array{subtotal:float}
	 */
	public function get_totals() {
		$subtotal = 0.0;
		foreach ( $this->get_contents() as $row ) {
			$price = $row['product']->get_price();
			if ( '' !== $price && null !== $price ) {
				$subtotal += (float) $price * $row['quantity'];
			}
		}
		return array( 'subtotal' => $subtotal );
	}
}
