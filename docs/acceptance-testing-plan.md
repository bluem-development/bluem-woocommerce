# Acceptance testing plan

This plan keeps the first useful acceptance suite cheap: prove that a real WordPress site still responds, that wp-admin is reachable, and that the Bluem admin page can be opened after logging in.

## Current implementation status

Implemented on the acceptance branch and wired into pull-request CI:

- Dockerized WordPress and MySQL preparation through `make acceptance_prepare`.
- PHP 8.4 provisioning for the complete acceptance job.
- Root WP-CLI execution for deterministic language installation in CI bind mounts.
- Isolated test options and completed plugin registration, so admin tests require no manual activation-form submission.
- Codeception smoke coverage for the public page, login form, and Bluem admin page.
- Failure artifacts containing Docker diagnostics and Codeception output when available.
- Unit coverage for callback/webhook payment-status transitions.
- Unit coverage for legacy WooCommerce gateway registration, preserving existing gateways and registering all five payment gateways.
- A separate `settings` acceptance group that saves and reloads harmless Bluem settings.
- A separate WooCommerce-backed `checkout` acceptance group that verifies all five gateway links appear in WooCommerce payment settings without calling Bluem.
- A Chromium Playwright job that verifies the JavaScript-capable Bluem settings page renders after administrator login.

Still planned:

- Full HTTP callback/webhook endpoint tests with mocked Bluem responses and order lookup.
- A real cart/order checkout submission test once a deterministic product and payment fixture are added.
- Broader Playwright interaction coverage for tabs, settings changes, and checkout UI behavior.

## Docker preparation and translation test

The Docker Compose setup includes a WP-CLI service. The preparation target
copies the current production package into Docker, starts WordPress and MySQL,
installs WordPress when necessary, and activates the Bluem plugin:

```bash
make acceptance_prepare
```

The translation integration test then switches locales inside WordPress and
checks that the compiled plugin catalogs are loaded:

```bash
make acceptance_translation_test
```

It verifies:

- `bluem-nl_NL.mo` exists and translates `Request created` to `Verzoek aangemaakt`;
- `bluem-en_US.mo` exists and returns the English source string;
- the `bluem` textdomain is loaded for both locales.

## Current smoke test

Run:

```bash
make acceptance_smoke_test
```

This first checks whether WordPress is reachable, then runs the Codeception `smoke` group from the existing Acceptance suite. It assumes the Docker WordPress site is already running at `http://localhost:8000`, with these credentials:

```text
username: wordpress
password: wordpress
```

The smoke group currently checks:

- the public home page responds
- the WordPress login page responds
- the Bluem admin page can be opened by an authenticated admin

The current smoke suite executes 3 tests with 7 assertions. The setup uses
placeholder test values only and does not call the Bluem API.

This is intentionally narrow. It should catch the most obvious site-breaking failures without turning every local change into a slow browser workflow.

## HPOS/order-storage integration test

The HPOS release work has a deterministic integration test that runs against real WordPress and WooCommerce containers. It creates an order through WooCommerce CRUD, persists Bluem transaction/entrance/mandate metadata, reloads the order, and queries it through the same custom query variables used by the gateway callbacks.

Run both data-store modes with:

```bash
make integration_test
```

The test uses isolated Docker volumes and runs once with HPOS enabled and once with the legacy posts-based order store. It does not call the Bluem API or require merchant credentials. `WOOCOMMERCE_VERSION` can override the default WooCommerce test version when checking another release.

## Near-term hardening

The preparation target now provides deterministic core setup and plugin activation.
The remaining hardening work is to extend that setup only when individual tests need
additional WooCommerce state or plugin configuration:

- start the Docker services
- wait for WordPress to respond
- install WordPress with known admin credentials when needed
- activate WooCommerce when the plugin needs it
- activate the Bluem plugin
- seed isolated test options and mark the plugin registration complete so
  admin acceptance tests do not depend on manual activation-form submission
- set permalink structure and any required plugin options
- create minimal sample data, such as a product or page only when a test needs it

Prefer WP-CLI for this setup. A one-off `wordpress:cli` container can run against the same Docker network and database as the WordPress container.

### Checkout and gateway registration coverage

The separate `checkout` group activates a pinned WooCommerce version and Bluem, loads the WooCommerce payment gateways, and verifies that these Bluem gateway IDs are available in the WooCommerce payment settings page:

- `bluem_payments_ideal`
- `bluem_payments_paypal`
- `bluem_payments_creditcard`
- `bluem_payments_sofort`
- `bluem_payments_cartebancaire`
- `bluem_mandates` is exposed to Blocks and remains covered by the existing Blocks compatibility unit tests; it is not a legacy payment gateway link.

Run the acceptance layers independently:

```bash
make acceptance_settings_test
make acceptance_checkout_test
```

The checkout test does not call the Bluem API. Its purpose is to catch missing gateway includes, PHP load errors, constructor failures, and WooCommerce registration regressions.

Keep richer flows in separate groups:

- `settings`: save and reload harmless plugin settings;
- `callbacks`: exercise mocked callback/webhook responses and order-status transitions;
- `checkout`: verify gateway registration and basic checkout rendering;
- `hpos`: retain the existing HPOS and legacy order-storage integration coverage.

Callback tests should explicitly cover `Success`, `Failure`, `Cancelled`, `Expired`, `New`, `Open`, and `Pending`, using mocked Bluem responses and no merchant credentials.

The callback/webhook handler layer is now covered by
`BluemPaymentStatusTransitionTest` and
`BluemPaymentCallbackHandlerTest`. The tests use mocked order objects, cover
all terminal and in-progress statuses, verify webhook-specific messages, and
reject missing, blank, or non-scalar transaction correlation data before an
order lookup. Full HTTP endpoint tests with mocked Bluem response objects remain
the next callback increment.

The smoke, settings, checkout, full acceptance, and translation targets all
prepare the Docker site before running. Checkout preparation is intentionally
separate so the fast smoke job does not need to install WooCommerce.

Keep the smoke target fast and boring. Add richer flows under separate groups, for example `settings`, `checkout`, or `callbacks`.

## GitHub Actions acceptance smoke job

The Dockerized smoke suite now runs in a dedicated `acceptance-smoke` GitHub
Actions job on pushes and pull requests. It calls the same
`make acceptance_smoke_test` target used locally, so the job prepares WordPress,
activates the production package, and runs the Codeception smoke group without
requiring Bluem credentials. The job has a bounded timeout and uploads
Codeception output and Docker diagnostics when it fails.

The acceptance tests use Codeception's Cest convention: acceptance classes must
be stored in files named `*Cest.php`. A file named `*Test.php` is not discovered
by the acceptance suite and can otherwise make a smoke command report success
with zero executed tests.

Do not make GitHub Actions responsible for discovering how the WordPress setup should work. First make the local Make targets deterministic, then call those same targets from CI.

Recommended implementation order:

- [x] implement `acceptance_prepare` locally
- [x] verify `make acceptance_prepare` followed by `make acceptance_smoke_test`
- [x] update `.github/workflows/ci.yml` to run Dockerized WordPress
- [x] run the same smoke target in CI
- [x] upload Codeception output and Docker logs when the smoke test fails

Suggested CI shape:

```yaml
- uses: actions/checkout@v4

- name: Install PHP dependencies
  run: composer install --no-interaction --prefer-dist

- name: Start WordPress
  run: docker compose up -d

- name: Prepare WordPress test site
  run: make acceptance_prepare

- name: Run acceptance smoke tests
  run: make acceptance_smoke_test
```

This should stay in the existing CI flow at first, or as a clearly named separate job such as `acceptance-smoke`. A separate job is cleaner once the suite starts pulling Docker logs or takes noticeably longer than PHPUnit.

## Playwright browser coverage

Playwright runs as a separate CI job after reusing the same deterministic
WordPress setup as Codeception. It is the right layer when the test needs real
browser behavior, JavaScript, screenshots, or reliable UI interaction with
modern admin pages.

Recommended first Playwright scope:

- open `/wp-login.php`
- log in with the known admin credentials
- navigate to `/wp-admin/admin.php?page=bluem-admin`
- assert that the Bluem admin page renders
- save one harmless setting and verify it persists
- capture screenshots on failure

Implemented files:

```text
package.json
playwright.config.ts
tests/playwright/bluem-admin.spec.ts
```

The CI job installs Chromium, runs `npm run test:e2e`, and uploads Playwright
traces, screenshots, and reports when available. The browser layer does not
install WordPress or prepare sample data.

Equivalent local commands:

```make
 npm install
 npx playwright install chromium
 npm run test:e2e
```

Suggested Playwright defaults:

- `baseURL`: `http://localhost:8000`
- browser: Chromium only at first
- retries: `1` in CI, `0` locally
- trace: retain on failure
- screenshot: only on failure
- video: off initially

Suggested environment variables:

```text
WP_BASE_URL=http://localhost:8000
WP_ADMIN_USER=wordpress
WP_ADMIN_PASSWORD=wordpress
```

The first Playwright implementation should reuse the same Docker/WP-CLI setup created for Codeception. The browser layer should not be responsible for installing WordPress or preparing sample data; it should only exercise the already-prepared site.

## Later CI path

Once local setup is repeatable, mirror the broader acceptance flow in GitHub Actions:

- check out the repository
- install PHP dependencies
- start Docker services
- run the WP-CLI setup
- run `make acceptance_smoke_test`
- upload Codeception output and container logs on failure

Browser tests run as a second job so simple PHP/site boot failures stay easy to diagnose.
