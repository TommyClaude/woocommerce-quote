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
7. **Settings page** (WooCommerce → Settings → Quote tab) — all behaviour configurable:
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

## 6. Decisions (confirmed with the user)
- **Button mode** → admin **setting**: (a) replace Add-to-Cart, or (b) show both.
- **RFQ scope** → admin **setting**: all products / by category / specific products /
  hidden-price products only / by user role.
- **Access** → admin **setting**: allow guests, or require login.
- **Language** → ship **English UI strings, fully translation-ready** (text domain
  `woocommerce-quote` + bundled `.pot`). Plugin targets a **global** audience, not VN-only.
- **Phasing** → build **Phase 1 (MVP) first** and ship it usable on its own, then build
  **Phase 2** (admin quotes → accept → order). Phase 2 is in scope, done after Phase 1.

Because button mode / scope / access are all settings, the **Settings page (item 7) and
the scope-resolution logic are core Phase 1 work**, not optional.
