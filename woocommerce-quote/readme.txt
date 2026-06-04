=== Request a Quote for WooCommerce ===
Contributors: tommyclaude
Tags: woocommerce, request a quote, rfq, quote, wholesale
Requires at least: 6.6
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Let customers collect products into a quote list and submit a request for a price instead of buying immediately. Store owners manage requests in wp-admin.

== Description ==

Request a Quote for WooCommerce adds an "Add to Quote" flow to your store. Customers
gather products into a quote list and submit a request with their contact details, and
the store owner reviews and replies with a price from wp-admin. Ideal for B2B, wholesale,
hidden-price and made-to-order catalogs.

All behaviour is driven by admin settings:

* **Button mode** — replace Add to Cart, or show both buttons.
* **RFQ scope** — all products, selected categories, selected products, hidden-price
  products only, or selected user roles.
* **Access** — allow guests, or require login.

The plugin is HPOS-compatible and ships with an English UI that is fully translation-ready
(text domain `woocommerce-quote`).

This is Phase 1 (MVP). See PLAN.md in the source repository for the full roadmap.

== Installation ==

1. Make sure WooCommerce is installed and active.
2. Upload the plugin folder to `/wp-content/plugins/`, or install the zip from
   Plugins → Add New → Upload Plugin.
3. Activate the plugin through the Plugins screen.
4. Configure it under the "WooCommerce Quote" menu.

== Changelog ==

= 0.5.0 =
* Transactional emails as WC_Email subclasses (manageable under WooCommerce →
  Settings → Emails): "New quote request" to the store and a "request received"
  confirmation to the customer, with HTML + plain-text templates.
* Bundled translation template (languages/woocommerce-quote.pot) plus a
  no-dependency POT generator (bin/make-pot.php).
* WordPress Playground blueprint (blueprint.json) that installs WooCommerce and
  the plugin and seeds a demo product, quote page and sample request; GitHub
  Pages preview launcher (index.html).
* Phase 1 (MVP) is now feature-complete.

= 0.4.0 =
* React admin app (built with @wordpress/components + @wordpress/element via
  @wordpress/scripts): a "WooCommerce Quote" top-level menu with a header
  (logo + Support) and Dashboard / Settings / About us tabs.
* Dashboard: stat cards, a 30-day trend chart (custom SVG) and recent requests.
* Settings: every option as a WP component, saved over REST.
* About us: plugin blurb + cross-sell.
* REST endpoints (`wcq/v1/settings`, `wcq/v1/stats`) guarded by
  manage_woocommerce; the native quote list nests under the new menu.

= 0.3.0 =
* Admin management of quote records on the native list table: Customer / Items /
  Total / Status / Date columns, a read-only detail meta box, and a status
  control (New / Quoted / Accepted / Rejected / Expired) saved with a nonce and
  capability check. Fires `wcq_quote_status_changed`.

= 0.2.0 =
* Quote list stored in the WooCommerce session (guests supported).
* AJAX add / update / remove with nonce + access checks.
* Add-to-Quote button on the shop loop and single product pages, honouring the
  button-mode and RFQ-scope settings.
* `[woocommerce_quote]` shortcode: quote list, request form (honeypot + nonce),
  validation, and a thank-you view.
* Submission handler creates a `wcq_quote` record, snapshots line items/totals,
  clears the list and fires `wcq_quote_created`.
* Settings storage/defaults/sanitization (`WCQ_Settings`).

= 0.1.0 =
* Initial scaffold: plugin bootstrap, WooCommerce-active guard, HPOS compatibility
  declaration, activation/deactivation, and the `wcq_quote` custom post type with its
  New / Quoted / Accepted / Rejected / Expired statuses.
