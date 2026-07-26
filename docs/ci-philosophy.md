# CI philosophy

CI should provide fast, layered evidence rather than treating one green test command as proof that the plugin is safe to release.

## Layers

- **Static safety:** validate Composer configuration and parse every project PHP file outside `vendor/` and generated build directories.
- **Unit behavior:** test deterministic logic and important contracts without WordPress, WooCommerce, Docker, or network dependencies.
- **Load and registration safety:** load each payment gateway class and, in acceptance tests, verify that WooCommerce registers the expected gateway IDs.
- **Integration behavior:** run the existing HPOS/legacy order-storage tests against real WordPress and WooCommerce containers.
- **Acceptance behavior:** keep smoke tests small, then add separate `settings`, `checkout`, and `callbacks` groups as those flows become deterministic.

## Principles

- Keep pull-request checks deterministic and independent of live Bluem credentials.
- Fail early on syntax, dependency, and plugin-load errors before slower Docker or browser jobs.
- Prefer mocked API responses for callback and status-transition tests.
- Keep smoke tests cheap and broad; keep payment-flow tests explicit and isolated.
- Reuse local Make targets in CI so local and hosted results exercise the same setup.
- Upload Docker and Codeception diagnostics when integration or acceptance jobs fail.
- Treat payment status handling as business-critical: `New`, `Open`, and `Pending` are in-progress states, not generic failures.

## Release protection

Before a release, CI should establish that the source parses, dependencies install from the lock file, all gateway classes load, unit tests pass, and the production package excludes development metadata. CI does not replace review of payment behavior or release-package inspection.
