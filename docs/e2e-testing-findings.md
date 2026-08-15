# E2E testing findings

This log captures plugin or library defects exposed while extending the local
WordPress/WooCommerce acceptance suite. Record a finding here instead of
weakening a test to accommodate faulty production behavior.

## 2026-08-15: legacy WooCommerce order editor omitted Bluem requests

The order-request metabox was registered only on WooCommerce's HPOS screen
(`woocommerce_page_wc-orders`). Stores using the legacy post-based order editor
therefore could not inspect linked Bluem requests from an order page.

Status: fixed in PR #69 by registering the same metabox through WordPress's
legacy `shop_order` hook and by accepting either a `WC_Order` or `WP_Post` in
its renderer. Browser acceptance coverage exercises the legacy editor; the
existing HPOS integration suite continues to cover query behavior under custom
order tables.
