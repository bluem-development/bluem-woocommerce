# Next three refactor units

Draft for review — 10 October 2026. Based on `refactor-increments.md`,
`refactor-wishlist.md`, the platform opportunities backlog, and the prior
three-batch planning chat. These are behavior-preserving extractions, with no
version bump or release preparation.

## Review of the preceding work

Merged in order: #87, #89, #67, #68. All four had fresh green checks and clean
mergeability before merging. Combined local validation passed on PHP 8.4.24:
219 tests, 664 assertions, project PHP lint, Composer validation/platform
requirements and diff whitespace checks.

PR #87 completes request lookup extraction. PR #89 completes the payment status
decision table, but does not complete the broader payment transition
orchestration batch. PRs #67 and #68 extract plugin/module status checks; their
review corrections preserve legacy loose comparisons and option values.

PRs #70 and #72 add Sentry diagnostics/metrics and PR #78 fixes request filtering.
They require separate functional review and are excluded from this refactor
batch. In particular, #70 introduces a second `bluem_woocommerce_create_client()`
definition beside the current diagnostics-aware factory, so its current patch
must not be merged as-is.

## 1. iDIN settings lookup

**Boundary:** `bluem_woocommerce_get_idin_option()` in `bluem-idin.php` and a
namespaced `BluemIdentitySettings` service. Compose the existing
`BluemOptionLookup` rather than inventing another settings base class.

Keep the public function and its signature. The adapter continues to obtain the
existing iDIN option definitions on every call; the service receives that array
and resolves keys. Preserve `array_key_exists()` semantics: explicit null,
false, zero and empty strings remain distinct from missing keys (which return
false). Leave translated definitions, description rendering, saved-value
assembly and `BluemConfigurationBuilder` merge order unchanged.

**Acceptance:** characterize the wrapper before extraction; test known and
missing keys plus null/false/zero/empty values and repeated calls with changed
definitions. Run the Unit suite and settings acceptance checks.

**Ownership:** only this lookup wrapper, the new identity settings class and its
tests. No identity validation, checkout enforcement or Blocks functionality.

## 2. Request log and link repositories

**Boundary:** extract `bluem_db_request_log()`, `bluem_db_get_logs_for_request()`,
`bluem_db_get_links_for_order()`, `bluem_db_get_links_for_request()` and
`bluem_db_create_link()` into focused injected repositories. Retain all five
procedural wrappers.

Adapters resolve the active database, current user, UTC timestamp and installed
database version on each call. Repositories own existing SQL and inserts;
workflow decisions stay outside them. Preserve table prefixes, binding types,
descending timestamp order, raw database return values, the version <= 1.2
link guard, and -1 failure results. Keep the currently unused log-data argument
compatible. Do not begin storing it.

**Acceptance:** characterize all wrappers first. Test database failures, empty
reads, the version boundary, custom prefixes and per-call globals. Verify with
real wpdb that request creation still returns the request ID after link/log
inserts change `insert_id`, and that updates retain their existing logging
behavior. Run Unit and HPOS/legacy integration suites.

**Ownership:** those five functions, new log/link repositories and tests.
Request creation/update/payload orchestration, cookies, schemas, atomic mandate
counters and durable guest references remain separate work.

## 3. Payment order-transition orchestration

**Boundary:** extract `applyPaymentStatusTransition()` from the bank-based
gateway into a plain service composed with `BluemPaymentStatus`. Keep the public
static gateway method as the compatibility adapter.

Pass the order operation and context-specific translated messages through
injected collaborators. WordPress translation calls and literal translatable
strings stay in the adapter. Preserve exactly one `update_status()` call when a
transition exists, no update for null transitions, existing note text/context
fallbacks, return values and exception propagation.

**Acceptance:** characterize the gateway adapter before rewiring. Cover every
known payment status against pending and non-pending orders, callback/webhook
and unknown contexts, unsupported statuses and repeated calls. Assert both
status and note text. Run Unit, gateway-loading, HPOS/legacy integration and
callback/checkout acceptance coverage available in CI.

**Ownership:** the transition method, new payment orchestration service and
tests. Do not rewrite callback parsing, signature checks, order correlation,
redirects, notifications or request persistence.

## Sequencing and future direction

All three units can branch independently from master after the preceding
refactors merge. They own disjoint functions/files and need no unmerged sibling.
Merge in the order above, checking the updated base and CI before each merge.

These boundaries prepare identity workflows, shared mandate persistence and
future platform adapters without implementing those features. Native
`payment_complete()`, new status semantics, scheduled reconciliation, Blocks,
REST routes and privacy behavior require separately scoped functional changes.
Do not reinterpret `SuccessManual`, `BankSelected` or `Refunded` during an
extraction. Preserve current behavior and record any discovered defects for a
separate correction.
