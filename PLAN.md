# WooCommerce Quote — Plan / Spec

> ⚠️ Assumptions are marked with ⚠️. Confirm/correct them with the user at the start
> of the session before building.
> ⚠️ Assumed scope: a self-contained **Request-a-Quote (RFQ)** plugin for a single
> store. NOT tiered B2B wholesale pricing (can be added later).

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
   - Config: enable globally, or per category / per product / per user role.
   - Option to replace, or sit beside, the Add-to-Cart button.
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
7. **Settings page** (under WooCommerce → Quote): enable rules, button text,
   recipient email, which page is the quote page.

### Phase 2 — Quoting workflow
- Admin enters a quoted price per line + total + note → sends a "Your quote" email.
- Customer link to **accept** → creates a WooCommerce order (draft) → checkout/pay.
- Hide prices + Add-to-cart for guests / chosen roles / chosen products.

### Phase 3 — nice to have
- PDF quote attachment, quote expiry dates, account "My quotes" area, reCAPTCHA, CSV export.

## 3. File structure
```
woocommerce-quote/                 (the plugin folder)
  woocommerce-quote.php            # header, constants (WCQ_VERSION), bootstrap, activation
  uninstall.php
  readme.txt
  includes/
    class-wcq-plugin.php           # singleton loader, hook wiring, WC-active guard, HPOS decl
    class-wcq-cpt.php              # register wcq_quote CPT + statuses
    class-wcq-session.php          # the customer's quote list (WC session / cookie)
    class-wcq-frontend.php         # buttons, quote-page render, asset enqueue
    class-wcq-ajax.php             # add / remove / update quote items (AJAX + nonce)
    class-wcq-request.php          # validate + save a submission -> wcq_quote
    class-wcq-emails.php           # WC_Email subclasses (or wp_mail wrappers)
    class-wcq-admin.php            # list columns, meta boxes, status changes
    class-wcq-settings.php         # WooCommerce settings tab / options
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
1. Plugin scaffold: main file, constants, singleton, **WC-active guard**, **HPOS decl**, activation/deactivation.
2. CPT + custom statuses.
3. Session quote list + AJAX add/remove/update.
4. Frontend: Add-to-Quote button + quote page (shortcode) + form.
5. Submission handler → create `wcq_quote`, clear list, thank-you.
6. Admin: list columns + detail meta box + status control.
7. Emails (admin + customer).
8. Settings page (enable rules, button text, recipient, quote page).
9. `blueprint.json` for Playground (install WooCommerce + this plugin) + smoke test.
10. Package the zip; bump version.

## 6. Open questions — confirm with the user before/while building
- ⚠️ Replace Add-to-Cart entirely, or show **both** buttons?
- ⚠️ Which products use RFQ — **all**, specific **categories**, or **hidden-price** only?
- ⚠️ Allow **guests**, or require login to request a quote?
- ⚠️ Build Phase 2 (accept → order) now, or ship Phase 1 first?
- ⚠️ Vietnamese UI strings out of the box, or English with i18n only?
