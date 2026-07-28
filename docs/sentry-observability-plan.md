# Bluem Sentry observability follow-up plan

This document is the development handoff for the initial Sentry integration introduced for version 1.5.2.

## Current state

Implemented locally:

- `sentry/sentry` 4.29.0 is installed through Composer.
- `src/Observability/BluemSentry.php` initializes Sentry early in plugin loading.
- Automatic PHP error, exception, and fatal-error handlers are enabled.
- Events are accepted only when a Bluem plugin or Bluem PHP frame is present.
- Request integration, request bodies, URLs, cookies, headers, user context, runtime/OS context, modules, breadcrumbs, local variables, and absolute filesystem paths are removed.
- Manual support reports use an allow-list containing only component, operation, safe status, and the generated error-report ID.
- Exception messages are scrubbed for URLs, email addresses, credentials, and order/payment-like identifiers.
- The existing central `bluem_error_report_email()` path also sends a separate sanitized event to Sentry.
- `BLUEM_SENTRY_DSN` can override the configured DSN and `BLUEM_SENTRY_ENABLED=false` disables reporting.
- Release metadata is set to 1.5.2.
- Unit coverage includes 14 tests and 53 assertions.

The first real event was attempted after implementation. Sentry rejected it with:

```text
event submission rejected with_reason: ProjectId
```

This indicates that project `4506286012891136` is currently disabled or unavailable. The SDK, transport path, event filtering, and scrubber all executed before the rejection.

## Immediate checklist

### 1. Enable and verify the Sentry project

- [ ] Enable project `4506286012891136` in Sentry.
- [ ] Confirm that the project is an active PHP project and that its client DSN is unchanged.
- [ ] Confirm that ingest is available in the configured US region.
- [ ] Confirm project quota, retention, alert, and notification settings.
- [ ] Confirm that the Sentry project is dedicated to Bluem WordPress/WooCommerce telemetry.

### 2. Send one controlled test event

- [ ] Run a synthetic event through the plugin integration after enabling the project.
- [ ] Confirm that Sentry returns an event ID and displays the event.
- [ ] Confirm release `bluem@1.5.2` and environment `production` are present.
- [ ] Confirm the event contains useful function, class, file, and line information.
- [ ] Confirm the event contains no request URL, query string, body, cookies, headers, user, order, transaction, entrance-code, payment, token, or raw response data.
- [ ] Confirm the stack paths are relative `bluem/...` paths and do not contain server usernames or site paths.
- [ ] Delete the synthetic event after verification if it is not useful for ongoing testing.

### 3. Decide the telemetry deployment policy

The current implementation uses the supplied public DSN as its default, so reporting is enabled when the plugin is active. Before publishing 1.5.2, make an explicit decision:

- [ ] Keep reporting enabled by default, with the disclosure in `readme.txt`.
- [ ] Or change to opt-in/configuration-only by requiring `BLUEM_SENTRY_DSN` in `wp-config.php`.
- [ ] Confirm the privacy notice, data-processing basis, retention period, and customer support policy with the appropriate owner.
- [ ] Document the disable mechanism for support and site administrators.

### 4. Configure Sentry-side defenses

- [ ] Enable default data scrubbing.
- [ ] Enable IP-address scrubbing.
- [ ] Add sensitive-field rules for token, authorization, cookie, password, email, phone, address, IBAN, card, order, transaction, entrance, mandate, payload, and response fields.
- [ ] Configure a project rate limit or quota guard against DSN abuse.
- [ ] Configure inbound filters for clearly irrelevant or synthetic events.
- [ ] Create an alert for new high-severity Bluem errors, while avoiding alerts for expected payment lifecycle statuses such as `New`, `Open`, and `Pending`.

### 5. Complete release verification

- [ ] Run `php -l bluem.php`.
- [ ] Run `php -l gateways/Bluem_Payment_Gateway.php`.
- [ ] Run `php -l gateways/Bluem_Bank_Based_Payment_Gateway.php`.
- [ ] Run `composer validate --no-check-publish`.
- [ ] Run `./vendor/bin/phpunit --testsuite Unit`.
- [ ] Run `git diff --check`.
- [ ] Review `composer audit --locked --no-interaction`; Composer previously reported three advisories in the dependency tree and these should be assessed before release.
- [ ] Build the production package with `make pre-deployment PLUGIN_VERSION=1.5.2`.
- [ ] Verify that the generated package contains the Sentry production dependency and excludes development files.
- [ ] Inspect the generated package for hidden files, `AGENTS.md`, `README.md`, raw test artifacts, and development metadata.
- [ ] Do not run `svn commit` until the release has been explicitly approved.

## Recommended next development improvements

These are desirable but not required to validate the initial 1.5.2 implementation.

### Safer configuration and consent

- [ ] Move from a compiled default DSN to an explicit deployment configuration or an admin setting with a clear opt-in.
- [ ] Add a small settings/status panel showing whether Sentry is enabled, disabled, or unavailable without displaying the full DSN.
- [ ] Add a “send test event” action restricted to administrators, protected by a nonce and capability check.
- [ ] Add a privacy notice link beside the setting and document retention and support use.

### Stronger automated testing

- [ ] Add an in-memory Sentry transport test so the complete serialized event can be inspected without network access.
- [ ] Test uncaught exceptions, PHP warnings, fatal errors, and manually reported errors separately.
- [ ] Add fixtures containing customer data, order data, payment responses, XML, access tokens, cookies, and authorization headers.
- [ ] Assert that every fixture is either removed or redacted before transport.
- [ ] Add a test proving unrelated WordPress, WooCommerce, theme, and third-party plugin errors are discarded.

### Operational quality

- [ ] Add safe gateway and Bluem operation tags where they are currently known.
- [ ] Add deterministic fingerprints for recurring Bluem callback and configuration failures.
- [ ] Add deduplication or rate limiting for repeated errors from one request or site.
- [ ] Evaluate whether synchronous Sentry sending adds unacceptable checkout or callback latency.
- [ ] If necessary, add a bounded queue or a Bluem-controlled relay rather than sending directly from payment requests.
- [ ] Consider a separate Sentry project or relay for development, acceptance, and production events.

### Future correlation

- [ ] Decide whether support needs a correlation identifier that can be matched to local Bluem logs.
- [ ] If needed, use a random non-business identifier; do not use or hash order IDs, transaction IDs, entrance codes, or customer identifiers as the correlation key.

## Reusable implementation prompt

Use this prompt when continuing the work:

> Continue the Bluem WooCommerce Sentry observability work from `docs/sentry-observability-plan.md`. First inspect the current repository state and do not overwrite unrelated changes. Work only on the next unchecked item that I name. Preserve the privacy contract: never send customer, order, payment, transaction, entrance-code, request, token, raw-response, user, cookie, header, or absolute filesystem-path data to Sentry. Keep Sentry failures non-blocking for checkout, callbacks, administration, and activation. Run the focused PHP lint, Composer validation, unit tests, and `git diff --check` after changes. Do not push or commit to SVN unless I explicitly request it.

## Release handoff

The implementation is ready for project activation and controlled event verification. It is not yet a completed WordPress.org release until the Sentry project is enabled, the actual event payload has been reviewed, the deployment policy is approved, and the production package has been inspected.
