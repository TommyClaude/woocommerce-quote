# WooCommerce Quote — Plan / Spec

> Scope: a self-contained **Request-a-Quote (RFQ)** plugin for a **global audience**
> (English UI, fully translation-ready). NOT tiered B2B wholesale pricing (later).
> The key behaviour decisions have been confirmed with the user — see **section 6**.
> All three behaviours (button mode, RFQ scope, guest access) are **admin settings**.

## 1. Goal & user stories
- As a **customer**, I can add products to a **Quote list** and submit a request with my
  contact info, instead of paying immediately.
- As a **store owner**, I can see all quote requests in wp-admin, reply with a price, and
  (later) convert an accepted quote into a WooCommerce order.
- Optionally **hide price + "Add to cart"** for chosen products and show "Request a quote".

## 2. Features

### Phase 1 — MVP (build this first)
1. **Add-to-Quote button**
   - Shows on the shop loop + single product pages.
   - **Scope** (admin setting): all products / selected categories / selected products /
     hidden-price products only / selected user roles.
   - **Button mode** (admin setting): replace Add-to-Cart, or show both buttons.
2. **Quote list (basket)**
   - A page rendered by shortcode `[woocommerce_quote]` showing added items.
   - Change quantity, remove item. Stored in the WooCommerce session / cookie (guests OK).
   - Optional "Quote (N)" counter in the menu.
3. **Quote request form**
   - Fields: name*, email*, phone, company, message. Honeypot + nonce.
   - On submit: validate → store → clear the quote list → show thank-you.
4. **Storage** — custom post type `wcq_quote` holding line items
   (product id, name, qty, price snapshot), customer fields, status, date.
5. **Admin management**
   - Custom list columns (customer, items, status, date), a detail meta box,
     statuses: **New / Quoted / Accepted / Rejected / Expired**.
6. **Emails**
   - To admin: "new quote request received".
   - To customer: "we received your request" confirmation.
7. **Settings tab** (inside the WooCommerce Quote admin app) — all behaviour configurable:
   - **Button mode**: replace Add-to-Cart / show both.
   - **RFQ scope**: all / selected categories / selected products / hidden-price only /
     selected user roles.
   - **Access**: allow guests / require login.
   - Button label text, recipient email(s), and which page renders the quote list.

### Phase 2 — Quoting workflow
- Admin enters a quoted price per line + total + note → sends a "Your quote" email.
- Customer link to **accept** → creates a WooCommerce order (draft) → checkout/pay.
- Hide prices + Add-to-cart for guests / chosen roles / chosen products.

### Phase 3 — nice to have
- PDF quote attachment, quote expiry dates, account "My quotes" area, reCAPTCHA, CSV export.

## 2.5 Admin UI shell — built with @wordpress/components
The plugin adds a **top-level admin menu "WooCommerce Quote"** that renders a React app
matching the reference screenshots. Build it with **@wordpress/components** +
**@wordpress/element** (via `@wordpress/scripts`). **Prefer a WP component for every
control**; write custom CSS/SVG only when no component exists (e.g. the chart, brand logo).

**Header** (top of the page): plugin logo + name + version + short tagline; on the right a
**"Support"** button (and optionally "Submit your idea") using `Button`.

**Tabs** (`TabPanel`): **Dashboard · Settings · About us**.
- **Dashboard** (statistics):
  - Stat cards (`Card`/`CardBody`): Total requests, Pending (New), Quoted, Accepted,
    Conversion rate.
  - A 30-day trend chart of incoming requests (**custom SVG** — WP has no chart component).
  - Recent requests list, linking to the native Quote Requests screen.
- **Settings**: every option as a WP component — Button mode (`RadioControl`), RFQ scope
  (`SelectControl` + `FormTokenField` for categories/products/roles), Access
  (`RadioControl`), button label (`TextControl`), recipient email(s) (`TextControl`),
  quote page (`SelectControl`). Save via REST (`@wordpress/api-fetch`); show `Notice`/`Spinner`.
- **About us**: studio blurb + "More plugins" cross-sell cards.

**Note**: individual quote **records** stay in the native `wcq_quote` CPT list table (robust
for CRUD); the React app covers Dashboard + Settings + About and links to that screen.

## 3. File structure
```
woocommerce-quote/                 (the plugin folder)
  woocommerce-quote.php            # header, constants (WCQ_VERSION), bootstrap, activation
  uninstall.php
  readme.txt
  package.json                     # @wordpress/scripts (wp-scripts) build for the admin app
  includes/
    class-wcq-plugin.php           # singleton loader, hook wiring, WC-active guard, HPOS decl
    class-wcq-cpt.php              # register wcq_quote CPT + statuses
    class-wcq-session.php          # the customer's quote list (WC session / cookie)
    class-wcq-frontend.php         # buttons, quote-page render, asset enqueue
    class-wcq-ajax.php             # add / remove / update quote items (AJAX + nonce)
    class-wcq-request.php          # validate + save a submission -> wcq_quote
    class-wcq-emails.php           # WC_Email subclasses (or wp_mail wrappers)
    class-wcq-admin.php            # top-level menu + mount React app; CPT list columns/meta boxes
    class-wcq-rest.php             # REST endpoints: settings get/save + dashboard stats
    class-wcq-settings.php         # settings storage, defaults, schema
  src/                             # admin React app (compiled by wp-scripts)
    index.js                       # mount the app
    app.js                         # shell: Header(logo + Support) + TabPanel
    tabs/dashboard.js              # stat cards + trend chart + recent requests
    tabs/settings.js               # all settings via @wordpress/components
    tabs/about.js                  # studio blurb + cross-sell
    components/                    # Header, StatCard, TrendChart (custom SVG), brand logo
    admin.scss                     # custom CSS ONLY where WP components don't cover
  build/                           # compiled output: index.js + index.asset.php (+ css) — enqueue this
  templates/
    quote-button.php
    quote-list.php
    quote-form.php
    emails/admin-new-quote.php
    emails/customer-quote-received.php
  assets/
    css/wcq-frontend.css
    js/wcq-frontend.js
  languages/
```

## 4. Data model — `wcq_quote` CPT
- `post_title`: auto `Quote #<id> — <customer name>`
- `post_status`: custom — `wcq-new`, `wcq-quoted`, `wcq-accepted`, `wcq-rejected`, `wcq-expired`
- meta:
  - `_wcq_customer` — name / email / phone / company / message
  - `_wcq_items` — array of { product_id, name, qty, price }
  - `_wcq_totals` — subtotal snapshot
  - `_wcq_quoted_price` — Phase 2

## 5. Build order for Phase 1
1. Plugin scaffold: main file, constants (`WCQ_VERSION` 0.1.0), singleton, **WC-active guard**, **HPOS decl**, activation/deactivation.
2. CPT `wcq_quote` + custom statuses.
3. Session quote list + AJAX add/remove/update.
4. Frontend: Add-to-Quote button + quote page (shortcode) + request form.
5. Submission handler → create `wcq_quote`, clear list, thank-you.
6. Quote-record management: native CPT list columns + detail meta box + status control.
7. **Admin React app scaffold** (`wp-scripts`): top-level "WooCommerce Quote" menu + Header(logo + Support) + `TabPanel`.
8. **Dashboard tab**: stat cards + 30-day trend chart + recent requests (data via REST).
9. **Settings tab**: all options via @wordpress/components, saved through REST (`class-wcq-rest.php`).
10. **About us tab**: studio blurb + cross-sell cards.
11. Emails (admin + customer).
12. `blueprint.json` for Playground (install WooCommerce + this plugin) + smoke test.
13. Run `npm run build`, package the zip, bump version.

## 6. Decisions (confirmed with the user)
- **Button mode** → admin **setting**: (a) replace Add-to-Cart, or (b) show both.
- **RFQ scope** → admin **setting**: all products / by category / specific products /
  hidden-price products only / by user role.
- **Access** → admin **setting**: allow guests, or require login.
- **Language** → ship **English UI strings, fully translation-ready** (text domain
  `woocommerce-quote` + bundled `.pot`). Plugin targets a **global** audience, not VN-only.
- **Phasing** → build **Phase 1 (MVP) first** and ship it usable on its own, then build
  **Phase 2** (admin quotes → accept → order). Phase 2 is in scope, done after Phase 1.
- **Admin UI** → a React app (top-level "WooCommerce Quote" menu) with a **header
  (logo + Support button)** and tabs **Dashboard (stats) / Settings / About us**, built
  with **@wordpress/components**; custom CSS/SVG only where WP components are missing.
  See the "Admin UI shell" section.

Because button mode / scope / access are all settings, the **Settings page (item 7) and
the scope-resolution logic are core Phase 1 work**, not optional.
