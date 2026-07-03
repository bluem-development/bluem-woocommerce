# Acceptance testing plan

This plan keeps the first useful acceptance suite cheap: prove that a real WordPress site still responds, that wp-admin is reachable, and that the Bluem admin page can be opened after logging in.

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

## Near-term hardening

The next step is deterministic site setup. The current suite still depends on the state already present in `docker/db_data`, which is useful while experimenting but not good enough for CI.

Add a setup layer that can:

- start the Docker services
- wait for WordPress to respond
- install WordPress with known admin credentials when needed
- activate WooCommerce when the plugin needs it
- activate the Bluem plugin
- set permalink structure and any required plugin options
- create minimal sample data, such as a product or page only when a test needs it

Prefer WP-CLI for this setup. A one-off `wordpress:cli` container can run against the same Docker network and database as the WordPress container.

Suggested target shape:

```make
acceptance_prepare:
	docker compose up -d
	# wait for db and WordPress
	# run wp core install if needed
	# activate required plugins

acceptance_smoke_test: acceptance_prepare
	php vendor/bin/codecept run Acceptance --group smoke --steps
```

The current `acceptance_smoke_test` target only has a cheap readiness check. After `acceptance_prepare` exists, make the smoke target depend on it instead of asking the developer to prepare the site manually.

Keep the smoke target fast and boring. Add richer flows under separate groups, for example `settings`, `checkout`, or `callbacks`.

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

## CI path

Once local setup is repeatable, mirror it in GitHub Actions:

- check out the repository
- install PHP dependencies
- start Docker services
- run the WP-CLI setup
- run `make acceptance_smoke_test`
- upload Codeception output and container logs on failure

Add Playwright to CI only after the smoke suite is stable. Browser tests should run as a second job or a clearly separate step so simple PHP/site boot failures stay easy to diagnose.
