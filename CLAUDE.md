# WooCommerce Quote — project context for Claude Code

> Handoff notes so a fresh Claude Code session can build this from scratch.
> **The user communicates in Vietnamese — reply in Vietnamese.**

## What this is
A WordPress plugin: **"Request a Quote" for WooCommerce** (WooCommerce RFQ).
Customers collect products into a quote list and **submit a quote request instead of
buying immediately**; the store owner manages requests in wp-admin and replies with a
price. Common for B2B / wholesale / hidden-price / made-to-order products.

Requires WordPress + **WooCommerce active**.

## Status
Brand new — only README so far. Build per `PLAN.md`, **Phase 1 (MVP) first**.

## Tech & conventions
- Standard WordPress/WooCommerce **PHP plugin**. The PHP/front-end side needs no build
  step, BUT the **admin UI is a React app** built with **@wordpress/components +
  @wordpress/element** via **@wordpress/scripts (`wp-scripts`)** — see PLAN.md
  "Admin UI shell". **Prefer a WP component for every control**; custom CSS/SVG only where
  none exists (charts, brand logo). Header has the plugin logo + a Support button; tabs are
  Dashboard (stats) / Settings / About us.
- Prefix everything: functions `wcq_`, classes `WCQ_`, options/meta `_wcq_` / `wcq_`,
  hooks `wcq_`, text domain `woocommerce-quote`.
- Security: **nonce** on every form/AJAX, `sanitize_*` on all input, `esc_*` on all
  output, `current_user_can()` checks in admin.
- i18n: wrap ALL user-facing strings in `__()` / `esc_html__()` with text domain
  `woocommerce-quote`. **Default UI is English; ship a bundled `.pot` so anyone can
  translate** — the plugin targets a global audience (not Vietnam-specific).
- Behaviour is driven by **admin settings**, not hardcoded: button mode (replace
  Add-to-Cart vs both), RFQ scope (all / category / product / hidden-price / role),
  and access (guests vs login required). See `PLAN.md` §6.
- Declare the WooCommerce dependency; on activation, show an admin notice and bail if
  WooCommerce is inactive.
- Be **HPOS-compatible** (declare `custom_order_tables` compatibility in `before_woocommerce_init`).
- **Bump the version on every build** (main-file header `Version:` + a `WCQ_VERSION`
  constant) so the user can track it. Start at `0.1.0`.

## Build / test
- Build the admin app first: `npm install` then `npm run build` (wp-scripts → outputs
  `build/index.js` + `build/index.asset.php`). Enqueue using that asset file's dependency
  array + version so WP loads `wp-components`, `wp-element`, etc.
- Package the plugin: `zip -qr woocommerce-quote.zip woocommerce-quote/` (zip the plugin
  folder — the actual plugin lives in a subfolder; keep repo meta out of the zip).
- **Test in WordPress Playground** with WooCommerce: add a `blueprint.json` that installs
  WooCommerce + this plugin and activates both (do this in Phase 1, step 9).
- Optional preview deploy: GitHub Pages + a Playground blueprint URL — the same pattern
  used in the user's other repo `TommyClaude/wpimage-preview` (look there for reference).

## Mobile note
The user is on iPhone. When sharing URLs: put each on its OWN line and **never** wrap a
URL in markdown bold/`**` — it breaks tap-to-open.

## Workflow each change
1. Make the change. 2. Bump version (header + `WCQ_VERSION`). 3. Re-zip + sanity-check.
4. Commit + push to `main`. 5. Share the Playground link to test.

## GitHub
Repo: `TommyClaude/woocommerce-quote` (private, branch `main`).
