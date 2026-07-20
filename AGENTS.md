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
- vendor development metadata such as the Bluem PHP package's `AGENTS.md`, `Makefile`, `README.md`, `changelog.md`, `composer.json`, `composer.lock`, `phpcs.xml`, `phpcs.xml.dist`, `phpunit.xml`, and `rector.php`, plus xmlseclibs' README, Composer, changelog, and PHPUnit files.

Keep the top-level `composer.json` and `composer.lock` in the production package: the plugin reads `composer.lock` at runtime when enriching support reports, and both files document the shipped dependency contract. Only development metadata inside `vendor/` is removed.

Before committing to SVN, verify:

```bash
find build svn-directory/tags/1.4.1 svn-directory/trunk -name '.*' -print
find build svn-directory/tags/1.4.1 svn-directory/trunk -path '*/.github*' -print
find build svn-directory/tags/1.4.1 svn-directory/trunk \( -name 'AGENTS.md' -o -name 'error-report.md' -o -path '*/docs' -o -name 'README.md' -o -name 'Makefile' -o -name 'phpunit.xml' -o -name 'phpcs.xml' -o -name 'phpcs.xml.dist' -o -name 'rector.php' -o -name 'changelog.md' \) -print
svn status svn-directory | rg '^\?|^!' || true
rg -n "Version: 1\.4\.1|Stable tag: 1\.4\.1|\"bluem-development/bluem-php\": \"\^2\.6\.1\"" svn-directory/tags/1.4.1 svn-directory/trunk -S --glob '!vendor/**'
```

The first three package checks should be empty unless there is a deliberate production file with that name. SVN status should have intentional `A`, `M`, and `D` entries only; no `?` or `!`. If a previous staging attempt left `!` entries after regenerating a package, remove those stale scheduled paths with `svn delete --force` before reviewing the final status.

## Observability

`bluem_error_report_email()` enriches support reports with plugin version, Bluem PHP version, PHP version, WordPress/WooCommerce versions, site URL, and a compact stack trace. When adding new error reports, include useful business context such as order ID, order status, transaction ID, entrance code, raw response object, and status string.

Avoid logging secrets or access tokens.

## Refactor Planning

See [docs/refactor-wishlist.md](docs/refactor-wishlist.md) for the prioritized technical wishlist gathered during the 1.4.1 release work.
