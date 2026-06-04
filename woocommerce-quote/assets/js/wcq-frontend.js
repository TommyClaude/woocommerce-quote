/* Request a Quote for WooCommerce — front-end behaviour. */
( function () {
	'use strict';

	var data = window.wcqData || {};
	var i18n = data.i18n || {};

	/**
	 * POST to admin-ajax with the shared nonce.
	 *
	 * @param {string} action  AJAX action suffix (e.g. "wcq_add_item").
	 * @param {Object} payload Key/value fields.
	 * @return {Promise<Object>} Parsed JSON response.
	 */
	function ajax( action, payload ) {
		var body = new URLSearchParams();
		body.append( 'action', action );
		body.append( 'nonce', data.nonce || '' );
		Object.keys( payload ).forEach( function ( key ) {
			body.append( key, payload[ key ] );
		} );

		return fetch( data.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString(),
		} ).then( function ( response ) {
			return response.json();
		} );
	}

	/**
	 * Update every quote counter on the page.
	 *
	 * @param {number} count New item count.
	 */
	function setCount( count ) {
		var nodes = document.querySelectorAll( '.wcq-count' );
		for ( var i = 0; i < nodes.length; i++ ) {
			nodes[ i ].textContent = count;
		}
	}

	/**
	 * Add a product to the quote list.
	 *
	 * @param {HTMLElement} button The clicked button.
	 */
	function handleAdd( button ) {
		var productId = button.getAttribute( 'data-product_id' );
		var quantity = 1;
		var variationId = 0;

		var form = button.closest( 'form.cart' );
		if ( form ) {
			var qtyInput = form.querySelector( 'input.qty, input[name="quantity"]' );
			if ( qtyInput && qtyInput.value ) {
				quantity = qtyInput.value;
			}
			var variationInput = form.querySelector( 'input[name="variation_id"]' );
			if ( variationInput && variationInput.value ) {
				variationId = variationInput.value;
			}
		}

		var original = button.innerHTML;
		button.classList.add( 'loading' );
		button.disabled = true;
		button.innerHTML = i18n.adding || 'Adding…';

		ajax( 'wcq_add_item', {
			product_id: productId,
			variation_id: variationId,
			quantity: quantity,
		} ).then( function ( res ) {
			button.classList.remove( 'loading' );
			button.disabled = false;

			if ( res && res.success ) {
				setCount( res.data.count );
				button.innerHTML = i18n.added || 'Added';
				window.setTimeout( function () {
					button.innerHTML = original;
				}, 2000 );
			} else {
				button.innerHTML = original;
				window.alert( ( res && res.data && res.data.message ) || i18n.error || 'Error' );
			}
		} ).catch( function () {
			button.classList.remove( 'loading' );
			button.disabled = false;
			button.innerHTML = original;
			window.alert( i18n.error || 'Error' );
		} );
	}

	/**
	 * Change an item's quantity, then refresh the page to re-render totals.
	 *
	 * @param {HTMLElement} input The quantity input.
	 */
	function handleUpdate( input ) {
		input.disabled = true;
		ajax( 'wcq_update_item', {
			key: input.getAttribute( 'data-key' ),
			quantity: input.value,
		} ).then( function () {
			window.location.reload();
		} ).catch( function () {
			input.disabled = false;
		} );
	}

	/**
	 * Remove an item, then refresh the page.
	 *
	 * @param {HTMLElement} link The remove link.
	 */
	function handleRemove( link ) {
		ajax( 'wcq_remove_item', {
			key: link.getAttribute( 'data-key' ),
		} ).then( function () {
			window.location.reload();
		} );
	}

	function onReady() {
		if ( typeof data.count !== 'undefined' ) {
			setCount( data.count );
		}

		document.addEventListener( 'click', function ( event ) {
			var addButton = event.target.closest( '.wcq-add-to-quote' );
			if ( addButton ) {
				event.preventDefault();
				handleAdd( addButton );
				return;
			}
			var removeLink = event.target.closest( '.wcq-remove' );
			if ( removeLink ) {
				event.preventDefault();
				handleRemove( removeLink );
			}
		} );

		document.addEventListener( 'change', function ( event ) {
			var qtyInput = event.target.closest( '.wcq-qty' );
			if ( qtyInput ) {
				handleUpdate( qtyInput );
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', onReady );
	} else {
		onReady();
	}
} )();
