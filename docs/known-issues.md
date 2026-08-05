# Known issues discovered during testing

This document records defects or suspicious behavior found while extending the
isolated WordPress/WooCommerce acceptance flow. These items are deliberately
kept separate from the regression tests: a test may reproduce an issue, but it
must not normalize or hide incorrect plugin behavior.

## Admin status refresh can fail in-progress payment states

Status: confirmed by code review; needs a focused regression test and fix.

Evidence:

- `bluem_update_request_by_id()` calls `PaymentStatus()` for payment requests.
- Its payment branch handles `Success`, `Cancelled`, and `Expired` explicitly.
- `Pending`, `Open`, `New`, and every other status fall into the final `else`
  branch, which marks the WooCommerce order as `failed`.
- The same shape exists in the mandate branch.
- This conflicts with the documented Bluem lifecycle: `New`, `Open`, and
  `Pending` are in-progress states and must not be treated as failures.

Follow-up:

- Add an HTTP/admin regression case with mocked `Pending`, `Open`, and `New`
  status responses.
- Make the admin refresh persist the request status while leaving the order
  pending/in progress for those states.
- Confirm the intended semantics for `SuccessManual`, `BankSelected`, and
  `Refunded` before changing their order behavior.

Relevant code: `bluem.php`, `bluem_update_request_by_id()`.

## iDIN shortcode callback URL relies on canonical slash redirect

Status: observed during isolated testing; assess separately.

The iDIN shortcode entry point generates a callback URL without a trailing
slash. In the Docker WordPress environment, the corresponding rewrite route
responds with a canonical 301 before the callback handler runs. The acceptance
flow uses the canonical slash form so it tests the handler itself.

Follow-up:

- Confirm that Bluem follows the redirect for all iDIN callback flows.
- Consider generating the canonical URL directly with `user_trailingslashit()`
  or an equivalent WordPress URL helper.
- Add a regression test for the externally generated callback URL if the
  redirect is not guaranteed by the provider.

Relevant code: `bluem_idin_execute()` and
`bluem_get_idin_shortcode_callback_url()`.

## Plugin reactivation resets configuration state

Status: observed in the Docker browser flow; intentionality is unclear.

Reactivating Bluem resets its setup/registration state and can remove required
configuration such as mandate identifiers. The acceptance flow must complete
the activation form again before normal admin pages are usable. This may be
intentional for first-run setup, but resetting existing merchant configuration
on a deactivate/reactivate cycle deserves a product decision and a dedicated
regression test.

Follow-up:

- Determine whether deactivation/reactivation is expected to preserve settings.
- If settings should survive, add an activation-hook regression test and fix
  the reset behavior.
- If the reset is intentional, make the behavior explicit in the UI and
  document which values are cleared.

## Library issues

No independent defect in `vendor/bluem-development/bluem-php` was established
by the current acceptance work. The local Bluem mock still exercises the real
library request construction, configuration validation, response parsing, and
plugin order-transition code. Any future library defect should be recorded here
with the library version, request/response fixture, and a minimal reproduction.
