# Acceptance testing plan

This plan keeps the first useful acceptance suite cheap: prove that a real WordPress site still responds, that wp-admin is reachable, and that the Bluem admin page can be opened after logging in.

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
- set permalink structure and any required plugin options
- create minimal sample data, such as a product or page only when a test needs it

Prefer WP-CLI for this setup. A one-off `wordpress:cli` container can run against the same Docker network and database as the WordPress container.

The smoke, full acceptance, and translation targets all prepare the Docker site
before running.

Keep the smoke target fast and boring. Add richer flows under separate groups, for example `settings`, `checkout`, or `callbacks`.

## GitHub Actions preliminary step

Before adding Playwright, add the Dockerized smoke suite to GitHub Actions. This should be a small CI change once `acceptance_prepare` works locally.

Do not make GitHub Actions responsible for discovering how the WordPress setup should work. First make the local Make targets deterministic, then call those same targets from CI.

Recommended implementation order:

- implement `acceptance_prepare` locally
- verify `make acceptance_prepare` followed by `make acceptance_smoke_test`
- update `.github/workflows/ci.yml` to start Dockerized WordPress
- run the same smoke target in CI
- upload Codeception output and Docker logs when the smoke test fails

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

## Playwright next steps

Do not add Playwright until the Codeception smoke path is stable and deterministic. Playwright is the right next layer when the test needs real browser behavior, JavaScript, screenshots, or reliable UI interaction with modern admin pages.

Recommended first Playwright scope:

- open `/wp-login.php`
- log in with the known admin credentials
- navigate to `/wp-admin/admin.php?page=bluem-admin`
- assert that the Bluem admin page renders
- save one harmless setting and verify it persists
- capture screenshots on failure

Suggested files for the next implementation prompt:

```text
package.json
playwright.config.ts
tests/playwright/bluem-admin.spec.ts
```

Suggested Make targets:

```make
playwright_install:
	npm install
	npx playwright install --with-deps chromium

acceptance_browser_test:
	npx playwright test
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

Add Playwright to CI only after the Codeception smoke suite is stable. Browser tests should run as a second job or a clearly separate step so simple PHP/site boot failures stay easy to diagnose.
