# Refactor Wishlist

This is a working backlog for refactors and cleanup discovered during the 1.4.1 release work. Priority 1 means highest value or risk reduction.

## Shortcode Mandate Flow: Decisions From the Entrance-Code Fix

The shortcode mandate flow was updated after a production report showed that its callback could lose the entrance code when user metadata or cookie storage was unavailable. The implementation is intentionally a small semantic correction, not a shortcode rewrite.

Decisions made:

- The `bluem_requests` database row is the authoritative record for an in-flight mandate, including `transaction_id`, `entrance_code`, status, and response payload.
- The callback uses the returned `mandateID` as its correlation key. It must not replace that ID with a possibly stale value from user metadata or cookie storage.
- User metadata and cookie-backed storage remain compatibility projections and fallbacks for legacy requests. Fallback values must match the returned mandate ID before they are used.
- A missing request row is tolerated for older transactions, but status updates, payload writes, and transaction notifications must be skipped when there is no row.
- A successful database-backed callback may refresh user metadata and cookie storage only when the request belongs to the current user. A guest request must not silently become associated with an unrelated logged-in user.
- `New`, `Open`, and `Pending` are in-progress mandate states. They must not be treated as unknown failures.
- The existing `[bluem_machtigingsformulier]` tag, markup, rewrite routes, cookie names, user-meta names, and procedural entrypoints remain stable for now.

Future consolidation direction:

- Keep the shortcode as a thin WordPress adapter while moving mandate correlation, request persistence, entrance-code resolution, and status decisions toward shared plain-PHP/domain services.
- Do not merge shortcode behavior directly into the WooCommerce gateway class: shortcode mandates have no order and have different result-page behavior. Share transaction semantics and persistence contracts instead.
- Preserve procedural wrapper functions during any future PHP file/class convention change so existing hooks, integrations, and shortcode consumers remain compatible while internals move.
- Before removing user metadata or cookie storage, confirm that all legacy shortcode, instant-mandate, and third-party integration transactions have a database record and a migration/repair path.
- Audit the WooCommerce gateway, instant mandate flow, and form rendering separately for the same status and request-resolution semantics. Do not assume that the current gateway callback is a complete reusable implementation; it still contains order-specific behavior and incomplete missing-row fallback handling.

The next useful refactor slice is a small shared mandate-request resolver/status decision layer with WordPress adapters around it. Shortcode registration and presentation modernization remain explicitly out of scope for that slice.

## 1. Separate WordPress Glue From the Domain Layer

Create a clearer boundary between WordPress/WooCommerce concerns and Bluem-specific domain behavior.

Current pain:

- `bluem.php` and feature files mix hooks, request parsing, rendering, persistence, emails, and business decisions.
- Gateway classes directly coordinate WooCommerce orders, Bluem API calls, status mapping, emails, and UI prompts.
- Testing business behavior requires a lot of WordPress context.

Target value:

- A smaller WordPress adapter layer for hooks, options, metadata, rendering, redirects, and mail.
- Plain PHP services for status mapping, payment callback decisions, error report payloads, and request persistence decisions.
- Easier unit tests without booting WordPress.

Suggested first slice:

- Extract payment status classification into a pure service or enum-like class.
- Unit test `New`, `Open`, `Pending`, `Success`, `Failure`, `Cancelled`, and `Expired` mapping without WooCommerce.

## 2. Centralize Bluem Status Semantics

Replace scattered string comparisons with one explicit status map.

Current pain:

- Status strings are compared inline in webhook/callback code.
- `New` was a valid status but fell into the unknown-failure branch.
- `SuccessManual`, `BankSelected`, and `Refunded` exist in Bluem schemas but do not yet have documented plugin semantics.

Target value:

- One canonical place to decide whether a status is successful, in-progress, failed, cancelled, expired, or unsupported.
- Easier coordination with `bluem-php`.
- Better error reports for genuinely unsupported statuses.

Suggested follow-up:

- Add a `PaymentStatusMapper` in plugin code.
- Add constants/helpers in `bluem-php` later so downstream integrations can reuse the same vocabulary.

## 3. Make Release Packaging Safer and More Deterministic

Turn the current Makefile/SVN process into a more defensive release command.

Current pain:

- Hidden files can leak if cleanup misses them.
- `svn add --force` and scheduling deletes are manual steps.
- Composer network/cache behavior can surprise release runs.
- Release verification is spread across human memory, Makefile, and README.

Target value:

- One command that builds, verifies, stages SVN tag/trunk, and stops before commit.
- A clear dry-run/status output.
- Repeatable checks for hidden files, vendor dev metadata, version markers, and dependency versions.

Suggested follow-up:

- Add `make stage-svn-release PLUGIN_VERSION=x.y.z`.
- Add a `make verify-release-package PLUGIN_VERSION=x.y.z` target.
- Keep `svn commit` separate and explicit.

## 4. Improve Error Reporting as a Structured Support Payload

Make error reports easier to inspect and correlate.

Current pain:

- Error reporting is useful, but payload structure depends heavily on each caller.
- Trace/environment are now included, but business context is still manual.

Target value:

- Consistent `service`, `function`, `message`, `order`, `transaction`, `environment`, and `trace` sections.
- Safer redaction of sensitive fields.
- Easier searching by `error_report_id`.

Suggested follow-up:

- Add an `ErrorReportData` builder/service.
- Add redaction rules for tokens and personal data.

## 5. Build Focused Tests Around Payment Callback Decisions

Add tests that prove order-update behavior for each payment status.

Current pain:

- The unit suite currently gives only a small signal.
- Callback behavior is high-risk but hard to test because WordPress/WooCommerce calls are embedded directly.

Target value:

- Confidence that a status like `New` cannot regress into order failure.
- Fast local checks for the core payment decision table.

Suggested follow-up:

- First test the pure status mapper.
- Then add a thin integration-ish test around a callback handler once the WordPress adapter boundary exists.

## 6. Keep `bluem-php` and Plugin Contracts Aligned

Treat the plugin and `bluem-php` as two owned packages with an explicit compatibility contract.

Current pain:

- The plugin needs urgent fixes sometimes before the library abstraction is ideal.
- Library schema/status behavior is not always reflected clearly in plugin code.

Target value:

- Cleaner upgrades.
- Shared constants/status helpers.
- Fewer plugin-side guesses.

Suggested follow-up:

- Add status constants or a small value object in `bluem-php`.
- Add release notes in both repos when a status or response shape changes.

## 7. Reduce Procedural Surface Area in `bluem.php`

Gradually move cohesive behavior out of the main plugin file.

Current pain:

- `bluem.php` is large and hard to scan.
- Small release changes require navigating unrelated admin/menu/rendering/error-reporting code.

Target value:

- Smaller files with clearer ownership.
- Easier code review and safer changes.

Suggested follow-up:

- Move support report helpers into `src/Observability`.
- Move admin menu/page registration into an admin module.
- Move configuration reads into a config adapter.

## 8. Improve Local Developer Experience

Make it easier to run useful checks without knowing the full WordPress setup.

Current pain:

- Full behavior needs WordPress/WooCommerce, but many high-value decisions could be tested without them.
- The release process assumes global Composer/SVN availability.

Target value:

- Fast, focused tests for pure logic.
- Clear setup diagnostics when Composer, SVN, or WordPress smoke infrastructure is missing.

Suggested follow-up:

- Add `make doctor`.
- Add `make quick-check` for syntax, Composer validation, unit tests, and diff whitespace.
- Document when acceptance tests require Docker/WordPress state.
