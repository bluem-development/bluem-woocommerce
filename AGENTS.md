# Agent Notes for Bluem WooCommerce

This repository is the developer source for the Bluem WordPress/WooCommerce plugin. Treat it as a release-sensitive WordPress plugin with a vendored production package flow into WordPress.org SVN.

## Current Architecture Map

- `bluem.php` is the main plugin entrypoint and still contains a large amount of procedural WordPress/admin/routing behavior.
- `gateways/` contains WooCommerce payment gateway classes. `Bluem_Bank_Based_Payment_Gateway.php` owns the shared ePayment callback and webhook handling for iDEAL, PayPal, credit card, SOFORT, and Carte Bancaire style flows.
- `bluem-idin.php`, `bluem-mandates*.php`, `bluem-integrations.php`, and `bluem-interface.php` contain feature-specific procedural modules.
- `src/` currently contains namespaced project code. At the time of writing this is mostly observability support, not the main domain layer.
- `vendor/bluem-development/bluem-php` is the Bluem PHP API dependency. We own that dependency too, so urgent compatibility fixes can be handled plugin-side first and cleaned up in `bluem-php` afterward.
- `readme.txt` is the WordPress.org plugin readme and release changelog. `README.md` is developer-facing documentation.

## Development Checks

Use the focused local checks first:

```bash
php -l bluem.php
php -l gateways/Bluem_Payment_Gateway.php
php -l gateways/Bluem_Bank_Based_Payment_Gateway.php
composer validate --no-check-publish
./vendor/bin/phpunit --testsuite Unit
git diff --check
```

The current unit suite is small, so passing it is useful but not enough to prove payment or WordPress behavior. For behavior touching callbacks, webhooks, settings, admin screens, or checkout, supplement with code-path review and, where possible, a local WordPress/WooCommerce smoke check.

### CI-parity checks before release

Before preparing an SVN release, run the same fast checks enforced by `.github/workflows/ci.yml`:

```bash
php -v
find . \( -path './vendor' -o -path './build' -o -path './docker' -o -path './svn-directory' \) -prune -o -name '*.php' -print0 | xargs -0 -n1 php -l
composer validate --no-check-publish
composer check-platform-reqs
./vendor/bin/phpunit --configuration ./.github/workflows/phpunit.xml
git diff --check
```

Run Composer and PHPUnit with PHP 8.4 or newer, matching the Composer requirement and the GitHub Actions `php_version: 8.4` configuration. Do not run PHPUnit directly from a PHP 8.3 host after installing dependencies with PHP 8.4; use a PHP 8.4 environment/container or the same PHPUnit action configuration as CI. The gateway-loading unit test must remain part of the Unit suite so all bank-based gateway files are loaded before release.

## Payment Status Notes

Bluem ePayment status handling is business-critical. Do not assume every non-success status is a failure.

Known status handling in the plugin:

- `Success`: mark pending orders as processing.
- `Failure`: mark pending orders as failed/expired-style failure.
- `Cancelled`: mark as cancelled.
- `Expired`: mark as failed.
- `New`, `Open`, `Pending`: treat as in-progress, not as an unknown failure.

`New` is a valid Bluem ePayment lifecycle status and must not fail an order. It should be treated like `Open`/`Pending`.

`bluem-php` schemas also mention statuses such as `SuccessManual`, `BankSelected`, and `Refunded`. Do not invent order behavior for these without confirming the intended business semantics.

### eMandate callback correlation

An eMandate callback must identify the WooCommerce order by its persisted
mandate metadata, never by incidental ordering such as the newest pending
order. Query through the active WooCommerce data store: use the classic
post-meta query shape for legacy storage and `meta_query` for HPOS. Process the
callback only when **exactly one** order matches; zero or multiple matches are a
safe no-op that should be reported with non-secret diagnostic context.

Regression coverage for a callback lookup must create both the intended older
order and a newer unrelated order, then assert that only the intended order is
updated. Run that scenario in both legacy and HPOS storage modes.

## Release Version Checklist

For a plugin release, update these files consistently:

- `bluem.php`: plugin header `Version`.
- `build.env`: `PLUGIN_VERSION`.
- `readme.txt`: `Stable tag` and changelog entry.
- `languages/bluem.pot` and `languages/bluem-nl_NL.po`: `Project-Id-Version` header when doing a version-only release prep.
- `composer.json` / `composer.lock`: dependency constraints and locked package versions, especially `bluem-development/bluem-php`.

After changing Composer constraints, run a narrow update when possible:

```bash
composer update bluem-development/bluem-php --with-dependencies --no-interaction
composer show bluem-development/bluem-php --locked
```

Composer may need network access for Packagist/GitHub. If sandboxed DNS fails, rerun the same narrow command with approval rather than changing the dependency manually.

### Release identity and publication

Each published version has three immutable counterparts: the merged source
commit, one GitHub release/tag, and one WordPress.org SVN tag. Use the same
version in all three. Before staging, check that neither remote tag exists. Do
not overwrite, delete, or repurpose an existing GitHub or SVN tag—even if the
WordPress directory has not yet selected it as the stable version. Publish a
new patch version instead.

Create the GitHub release from the merged release commit and publish the SVN
tag and trunk from the corresponding reviewed production package. Verify both
remote artifacts afterwards (version markers, dependency version, and package
contents). The GitHub release and SVN publication may be created close
together, but neither may describe a different source/package version.

## WordPress.org SVN Release Flow

The repository has a local SVN working copy at `svn-directory/`, which is ignored by Git. The generated production package is written to `build/`, also ignored by Git.

The intended flow is:

```bash
make pre-deployment PLUGIN_VERSION=1.4.1
make add-tag PLUGIN_VERSION=1.4.1
make update-trunk PLUGIN_VERSION=1.4.1
svn add --force svn-directory/tags/1.4.1 svn-directory/trunk
svn status svn-directory | awk '/^!/ {print $2}' | xargs svn delete --force
svn status svn-directory
```

Use a clean, up-to-date SVN working copy for each release attempt. Do not
reuse a dirty, incomplete, or checksum-corrupt checkout; create a fresh sparse
checkout instead. Commit the new tag and trunk together in a single SVN commit
so they cannot be published as separate releases. If the client reports a
failed or interrupted commit, first inspect the remote tag and trunk before
retrying: the server may already have accepted the commit.

Only commit SVN after reviewing the status:

```bash
svn commit svn-directory -m "Release version 1.4.1"
```

Do not run the SVN commit until the user has explicitly asked for it.

## SVN Packaging Pitfalls

Be careful with hidden files and vendored development metadata.

The Makefile cleanup should prevent these from entering `build/`, `svn-directory/tags/<version>/`, or `svn-directory/trunk/`:

- top-level hidden development files such as `.vscode`, `.php-cs-fixer.dist.php`, `.phpunit.result.cache`, `.travis.yml`, `.svnignore`;
- internal repository instructions and support artifacts: `AGENTS.md`, `error-report.md`, and `docs/`;
- vendor `.github` directories;
- `vendor/bluem-development/bluem-php/.githooks`;
- `vendor/bluem-development/bluem-php/examples`;
- `vendor/bluem-development/bluem-php/tests`;
- local build/repo files such as `README.md`, `Makefile`, `Dockerfile`, `docker-compose.yml`, `codeception.yml`, `phpunit.xml`, `psalm.xml`, `loadenv.sh`, and `build.env`.
- vendor development metadata such as the Bluem PHP package's `AGENTS.md`, `Makefile`, `README.md`, `RELEASING.md`, `v2-api-plan.md`, `changelog.md`, `composer.json`, `composer.lock`, `phpcs.xml`, `phpcs.xml.dist`, `phpunit.xml`, and `rector.php`, plus xmlseclibs' README, Composer, changelog, and PHPUnit files.

Keep the top-level `composer.json` and `composer.lock` in the production package: the plugin reads `composer.lock` at runtime when enriching support reports, and both files document the shipped dependency contract. Only development metadata inside `vendor/` is removed.

Before committing to SVN, verify:

```bash
find build svn-directory/tags/1.4.1 svn-directory/trunk -name '.*' -print
find build svn-directory/tags/1.4.1 svn-directory/trunk -path '*/.github*' -print
find build svn-directory/tags/1.4.1 svn-directory/trunk \( -name 'AGENTS.md' -o -name 'error-report.md' -o -path '*/docs' -o -name 'README.md' -o -name 'RELEASING.md' -o -name 'v2-api-plan.md' -o -name 'Makefile' -o -name 'phpunit.xml' -o -name 'phpcs.xml' -o -name 'phpcs.xml.dist' -o -name 'rector.php' -o -name 'changelog.md' \) -print
svn status svn-directory | rg '^\?|^!' || true
rg -n "Version: 1\.4\.1|Stable tag: 1\.4\.1|\"bluem-development/bluem-php\": \"\^2\.6\.1\"" svn-directory/tags/1.4.1 svn-directory/trunk -S --glob '!vendor/**'
```

The first three package checks should be empty unless there is a deliberate production file with that name. SVN status should have intentional `A`, `M`, and `D` entries only; no `?` or `!`. If a previous staging attempt left `!` entries after regenerating a package, remove those stale scheduled paths with `svn delete --force` before reviewing the final status.

## Observability

`bluem_error_report_email()` enriches support reports with plugin version, Bluem PHP version, PHP version, WordPress/WooCommerce versions, site URL, and a compact stack trace. When adding new error reports, include useful business context such as order ID, order status, transaction ID, entrance code, raw response object, and status string.

Avoid logging secrets or access tokens.

## Refactor Planning

See [docs/refactor-wishlist.md](docs/refactor-wishlist.md) for the prioritized technical wishlist gathered during the 1.4.1 release work.
