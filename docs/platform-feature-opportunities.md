# WordPress 7 and WooCommerce feature opportunities

> Backlog snapshot: 20 July 2026. Reviewed against WordPress 7.0.2 and WooCommerce 10.9.4.

This is a focused sweep of valuable platform capabilities that the Bluem plugin does not currently use, or only uses through older compatibility paths. It is a product backlog, not a commitment to implement every item. Priorities reflect customer impact and platform risk.

## Current baseline

- The gateways extend `WC_Payment_Gateway`, but declare support only for `products`; refunds, tokenization, subscriptions, and other gateway capabilities are not enabled ([`gateways/Bluem_Payment_Gateway.php`](../gateways/Bluem_Payment_Gateway.php)).
- Order-related Bluem values are written with `update_post_meta()` and queried through `woocommerce_order_data_store_cpt_get_orders_query`, which is a posts/CPT-specific path ([`gateways/Bluem_Bank_Based_Payment_Gateway.php`](../gateways/Bluem_Bank_Based_Payment_Gateway.php)).
- Checkout identity checks are attached to classic checkout hooks and `template_redirect`; there is no Cart/Checkout Blocks integration ([`bluem-idin.php`](../bluem-idin.php)).
- Mandate and identity front ends are shortcodes and custom rewrite endpoints; there are no registered blocks, REST routes, or Store API extensions.
- Plugin notifications use direct `wp_mail()` calls rather than WooCommerce email classes or the newer WooCommerce transactional email log ([`bluem.php`](../bluem.php)).

## Prioritized opportunities

### P0 — Make the existing payment flows platform-safe

#### 1. Add full HPOS compatibility

**Opportunity:** Support WooCommerce High-Performance Order Storage as an authoritative datastore and declare compatibility so merchants are not prevented from enabling HPOS.

**Why it matters:** HPOS is the modern order storage model and is enabled by default for new WooCommerce installations. Direct post-meta writes and the CPT-only order query filter are the largest compatibility risk in the current plugin.

**Likely scope:**

- Replace order `get_post_meta()`/`update_post_meta()` calls with `WC_Order` CRUD methods such as `get_meta()` and `update_meta_data()` followed by `save()`.
- Replace custom CPT query variables with datastore-neutral `wc_get_orders()` queries or a supported HPOS query extension.
- Add the WooCommerce `custom_order_tables` compatibility declaration and test with HPOS both enabled and in compatibility mode.
- Add migration/backfill handling for existing Bluem order metadata.

**Acceptance signal:** HPOS can be enabled without an incompatibility warning, and callbacks can resolve orders by transaction ID and entrance code in both storage modes.

Sources: [WooCommerce HPOS documentation](https://developer.woocommerce.com/docs/features/orders/high-performance-order-storage/), [HPOS compatibility declarations for Cart/Checkout extensions](https://developer.woocommerce.com/docs/block-development/extensible-blocks/cart-and-checkout-blocks/).

#### 2. Add WooCommerce Cart and Checkout Blocks payment integrations

**Opportunity:** Make every Bluem payment method a first-class payment option in the block-based checkout, including correct redirect/error behavior and frontend assets.

**Why it matters:** The current gateway API can provide legacy fallback processing, but it does not provide the payment-method registration, client-side UI, or Store API context expected by modern checkout. A block checkout can therefore miss payment-specific UI and validation behavior.

**Likely scope:**

- Add an `AbstractPaymentMethodType` integration for each active Bluem gateway.
- Register the payment method in JavaScript with the title, description, icon, and any iDEAL BIC selection data.
- Use the Store API payment context for order processing and return Bluem redirects through `PaymentResult`.
- Keep the shortcode checkout path working during a staged migration.

Sources: [WooCommerce payment method integration](https://developer.woocommerce.com/docs/block-development/extensible-blocks/cart-and-checkout-blocks/checkout-payment-methods/payment-method-integration), [Checkout Block extensibility overview](https://developer.woocommerce.com/docs/block-development/extensible-blocks/cart-and-checkout-blocks/).

#### 3. Make iDIN identity and age verification block-compatible

**Opportunity:** Replace the classic-checkout-only notice/validation path with a Checkout Block extension that can display the verification state, launch identification, and prevent placement when the required verification is missing.

**Why it matters:** The existing implementation relies on `woocommerce_review_order_before_payment`, `woocommerce_after_checkout_validation`, and `template_redirect`. Those are not a complete contract for the block checkout, so age-restricted products can lose the intended UX or enforcement path.

**Likely scope:**

- Add a server-rendered or interactive iDIN verification component to the checkout block.
- Use the Block validation store and Store API validation for authoritative enforcement.
- Use the additional checkout field/extension data APIs only for non-sensitive state or user choices; never expose identity payloads to the browser unnecessarily.
- Test guest checkout, logged-in checkout, mixed-age carts, and re-verification after returning from Bluem.

Sources: [Checkout Block validation store](https://developer.woocommerce.com/docs/block-development/reference/data-store/validation), [Additional checkout fields and validation](https://developer.woocommerce.com/docs/block-development/extensible-blocks/cart-and-checkout-blocks/additional-checkout-fields), [Extending the Store API](https://developer.woocommerce.com/docs/apis/store-api/extending-store-api/extend-store-api-add-custom-fields/).

### P1 — Improve payment lifecycle and reliability

#### 4. Use WooCommerce’s native payment lifecycle and refund contract

**Opportunity:** Store the transaction ID in WooCommerce’s standard payment fields, use `payment_complete()` where the Bluem status is successful, expose a transaction link, and implement `process_refund()` if Bluem supports refunds.

**Why it matters:** The plugin currently updates order statuses directly and stores a separate `bluem_transactionid` field. Native lifecycle methods improve compatibility with order screens, reports, emails, analytics, and other extensions.

**Feasibility gate:** The current vendored `bluem-php` package exposes payment-status requests but no obvious refund operation. Confirm the Bluem API contract first. If remote refunds are not supported, do not advertise WooCommerce refund support; instead provide a clear manual-refund/on-hold workflow.

Sources: [WooCommerce Payment Gateway API](https://developer.woocommerce.com/docs/features/payments/payment-gateway-api/), [WooCommerce refund support](https://developer.woocommerce.com/2014/08/05/wc-2-2-payment-gateways-adding-refund-support-and-transaction-ids/).

#### 5. Add resilient status reconciliation with Action Scheduler

**Opportunity:** Queue a bounded status check when a payment or mandate is pending, retry transient Bluem/API failures with backoff, and surface stale pending transactions to administrators.

**Why it matters:** The current design depends heavily on the return callback and webhook arriving successfully. A scheduled reconciliation path reduces orders stuck in `pending` because of a timeout, customer tab closure, or delivery problem.

**Likely scope:**

- Schedule one idempotent action per in-flight transaction.
- Use Bluem payment/mandate status requests as a fallback, not as a replacement for signed webhooks.
- Make all status transitions idempotent and preserve valid `New`, `Open`, and `Pending` states.
- Cancel scheduled actions after terminal status, deactivation, or uninstall.

Source: [WooCommerce guidance on scheduled actions and cleanup](https://developer.woocommerce.com/docs/extensions/core-concepts/handling-deactivation-and-uninstallation/).

#### 6. Add WooCommerce payment tokens and, conditionally, recurring payments

**Opportunity:** Let customers reuse an eligible Bluem payment method or mandate from My Account and checkout; investigate WooCommerce Subscriptions support for Bluem mandates.

**Feasibility gate:** This should be split into two decisions. Tokenization requires a stable Bluem-side token/mandate reference and a secure customer association. Subscriptions additionally require a documented Bluem operation for future charges, cancellation, retries, and amount/date changes. Do not declare `tokenization` or `subscriptions` in `$this->supports` until those server-side contracts exist.

Source: [WooCommerce Payment Token API](https://developer.woocommerce.com/docs/features/payments/payment-token-api), [WooCommerce payment-method support flags](https://developer.woocommerce.com/docs/code-snippets/check_payment_method_support/).

### P1 — Modernize the WordPress integration surface

#### 7. Introduce a versioned REST API for Bluem status and integrations

**Opportunity:** Provide authenticated REST routes for transaction lookup, identity/mandate status, admin diagnostics, and supported actions, while keeping signed external webhook routes separate.

**Why it matters:** The plugin currently uses custom rewrite rules and query variables for callbacks and integrations. A versioned REST surface is easier for headless stores, mobile/account portals, automation, and third-party integrations to discover and secure.

**Likely scope:**

- Add `bluem/v1` routes with explicit schemas, capability checks, nonce/application-password support as appropriate, and mandatory `permission_callback` values.
- Keep the public Bluem webhook endpoint narrowly scoped and validate the Bluem signature before looking up or mutating an order.
- Return status vocabulary consistently across REST, admin, and WooCommerce callbacks.

Source: [`register_rest_route()` documentation](https://developer.wordpress.org/reference/functions/register_rest_route/).

#### 8. Replace shortcode-only forms with dynamic WordPress blocks

**Opportunity:** Add editor-visible blocks for iDIN identification, mandate authorization, and transaction-result/status displays, while retaining the existing shortcodes as compatibility wrappers.

**Why it matters:** Shortcodes cannot be configured or previewed as well in block themes and Site Editor flows. WordPress 7 supports PHP-only server-rendered blocks, and the Interactivity API can add progressive enhancement without turning sensitive transaction state into client-side state.

**Likely scope:**

- Register dynamic blocks with server-side rendering and block supports.
- Add block attributes for scenario, minimum age, copy, redirect behavior, and logo visibility.
- Use the Interactivity API only for UI state; keep transaction correlation and authorization server-side.
- Add a deprecation/migration path from the existing shortcodes.

Sources: [WordPress 7.0 Field Guide](https://make.wordpress.org/core/2026/05/14/wordpress-7-0-field-guide/), [WordPress block registration](https://developer.wordpress.org/block-editor/getting-started/fundamentals/registration-of-a-block/).

#### 9. Implement WordPress privacy export, erasure, and retention controls

**Opportunity:** Register personal-data exporters and erasers for iDIN data, user metadata, guest storage, request payloads, logs, and order-linked records; document retention and redaction behavior.

**Why it matters:** The plugin handles names, addresses, birth dates, email addresses, IBAN-related data, cookies, and identity results. The current repository has no privacy exporter/eraser hooks, and the custom request tables need an explicit user/order deletion policy.

**Likely scope:**

- Map every personal-data field and external sharing purpose.
- Export data in a useful, human-readable format without exposing access tokens or raw secrets.
- Erase or anonymize data according to configurable retention rules, with documented exceptions for financial/order records.
- Remove guest storage and request links when their retention period ends.

Sources: [WordPress Privacy Handbook](https://developer.wordpress.org/plugins/privacy/), [Privacy exporter and eraser hooks](https://developer.wordpress.org/plugins/privacy/privacy-related-options-hooks-and-capabilities/).

### P2 — Improve merchant operations and future-facing integrations

#### 10. Integrate with WooCommerce email classes and transactional email logs

**Opportunity:** Convert order-related Bluem notifications into WooCommerce email actions/templates, and route operational diagnostics through a merchant-visible log where appropriate.

**Why it matters:** The plugin currently sends HTML directly with `wp_mail()`. WooCommerce 10.9 adds transactional email logging under WooCommerce Status/Logs, but direct mail bypasses that troubleshooting surface.

**Likely scope:**

- Add a dedicated “Bluem payment pending/failed” email or extend appropriate WooCommerce order emails.
- Keep support reports separate and redact sensitive fields before logging or emailing.
- Add order ID, transaction ID, status, and correlation ID to the email/log context.

Source: [WooCommerce 10.9 release notes](https://developer.woocommerce.com/2026/06/23/woocommerce-10-9/).

#### 11. Rebuild the transaction request admin screen on DataViews/DataForms

**Opportunity:** Replace the custom table-heavy request UI with searchable, filterable, responsive views that use standard WordPress/WooCommerce admin patterns.

**Why it matters:** The request table is operationally important but is currently rendered through bespoke PHP views. WordPress 7 expands DataViews/DataForms, which can provide consistent filtering, details, and future bulk actions.

**Guardrail:** Do not expose raw identity payloads by default in a broad list view. Keep sensitive details behind capability checks and an explicit detail screen.

Source: [WordPress 7.0 Field Guide — DataViews and DataForms](https://make.wordpress.org/core/2026/05/14/wordpress-7-0-field-guide/).

#### 12. Expose safe Bluem abilities for diagnostics and automation

**Opportunity:** Register read-only WordPress Abilities/WooCommerce abilities for “find transaction,” “explain current status,” “show pending transactions,” and “prepare a redacted support report.” On WordPress 7, these could be consumed by the Command Palette, REST, WP-CLI, or the provider-agnostic AI Client.

**Why it matters:** This could make support and merchant operations much faster without giving an AI or automation system permission to initiate payments, alter identity results, or refund orders.

**Guardrails:** Opt-in, capability-checked, read-only first; redact tokens, raw identity data, and full payloads; require an explicit human confirmation for any future mutating ability.

Sources: [WordPress 7.0 AI and Abilities overview](https://make.wordpress.org/core/2026/05/14/wordpress-7-0-field-guide/), [WooCommerce 10.9 canonical abilities](https://developer.woocommerce.com/2026/05/12/mcp-abilities-api-10-9/).

## Suggested release grouping

### Next minor release

- HPOS compatibility audit and CRUD migration for order metadata.
- Checkout Blocks compatibility declaration and a minimal payment-method integration.
- Block-compatible iDIN enforcement for the highest-risk age-verification path.
- Signed, idempotent status reconciliation for stale pending transactions.
- Privacy inventory plus exporter/eraser implementation.

### Next major release

- Full native WooCommerce payment lifecycle: standard transaction ID, refund decision, and payment-token decision.
- REST API and dynamic blocks with shortcode compatibility wrappers.
- WooCommerce email integration and a redesigned transaction admin screen.

### Experimental / discovery track

- WooCommerce Subscriptions support, only after Bluem confirms recurring-charge semantics.
- WordPress Abilities/AI-assisted diagnostics, strictly read-only and opt-in.

## Out of scope for this sweep

- Adopting every WordPress 7 editor feature (fonts, navigation overlays, gallery lightboxes, and visual revisions) without a direct Bluem user journey.
- Enabling WooCommerce gateway flags merely because the platform supports them; each flag requires a confirmed Bluem API contract and end-to-end tests.
- Replacing existing callback URLs immediately; migration should preserve existing transactions and provide a compatibility window.
