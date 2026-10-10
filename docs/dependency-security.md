# Dependency security

The Bluem WooCommerce maintainers own dependency triage. DaanRijpkema is the
initial GitHub contact; pluginsupport@bluem.nl is the existing support escalation
contact. Assign each finding to a named maintainer before starting remediation.

## Checks and update policy

`Dependency security` runs on every pull request, master push, Monday at 07:17
UTC, and manual dispatch. It audits the complete Composer lock (including dev)
and npm dependencies at all vulnerability severities. The audit commands print
advisories and fail the job on vulnerabilities; registry/network failures also
fail. Never add `continue-on-error`, `|| true`, `--no-dev`, severity exclusions,
or blanket ignores to these audits. Production dependency isolation checks the
actual `make pre-deployment` package against the runtime lock and rejects dev
packages and npm tooling. The top-level Composer manifest and lock stay shipped
for support diagnostics, although the dev packages themselves do not ship.

Dependabot checks Composer, npm and GitHub Actions weekly with at most five
open version PRs per ecosystem. Routine minor/patch Composer changes are grouped
by runtime versus development usage. Major version updates remain individual.
The coding standards security group uses `applies-to: security-updates` and
explicit package patterns; runtime security updates remain individual. Security
updates operate independently of the weekly version schedule. Grouping is a
review aid, not a guarantee of compatibility. Review every resolved lock diff.
See [GitHub security update configuration](https://docs.github.com/en/code-security/how-tos/secure-your-supply-chain/secure-your-dependencies/configure-security-updates)
and [Dependabot options](https://docs.github.com/en/code-security/reference/supply-chain-security/dependabot-options-reference).

Update PRs run the existing PHP 8.4/8.5 unit, HPOS/legacy storage, WordPress,
checkout and browser checks. npm installs in CI use `npm ci` for reproducibility.
Automatic merging is not configured. Preserve PHP >=8.4 and WordPress >=6.0
support and the existing WooCommerce compatibility tests. Check the solver's
platform requirements as well as the plugin header when updating dependencies.

## Notification and repository settings

Scheduled failures use GitHub Actions' existing failed-workflow notifications.
DaanRijpkema should manually dispatch this workflow after merge, enable
email or web Actions notifications in personal Settings > Notifications >
Actions, selecting "Only notify for failed workflows", and confirm receipt.
The user who last modifies the cron syntax (or re-enables a disabled schedule)
receives scheduled-run notifications; when that user changes, hand over this responsibility explicitly. See [GitHub workflow notifications](https://docs.github.com/en/actions/concepts/workflows-and-actions/notifications-for-workflow-runs).
The assigned maintainer reviews the Actions tab each Monday and opens a tracked
issue for an actionable failure. This workflow needs only `contents: read` and
uses no repository secrets or issue-write token.

On 2026-10-10, API checks confirmed Dependabot alerts enabled (HTTP 204) and
security updates enabled and not paused. The dependency graph is operating:
GitHub reports the three lockfile development alerts. No setting change was
needed. An administrator must retain Dependency graph, Dependabot alerts and
Dependabot security updates under Settings > Advanced Security. YAML grouping
rules control the selected security group without requiring broad UI grouping.

Master's protection currently has no required status checks. After the new
workflow has run, an administrator must require `Composer audit`, `npm audit`,
`Production dependency isolation`, and the relevant existing CI jobs in the
master ruleset/branch protection. Review dependency PRs before merging, and
confirm the checks belong to GitHub Actions. Repository settings, notification
subscriptions and branch protection are not enforced by committed YAML.

## Triage and closure

1. Read the exact advisory ID, affected range and fixed releases; reproduce the
   full audit with `composer audit --locked` and `npm audit --include=dev`.
   Use `composer why` / `composer prohibits` to locate transitive constraints.
2. Determine whether the package ships at runtime, accepts attacker-controlled
   input, or processes untrusted PR code in developer/CI tooling. Prioritize
   runtime critical/high findings and any exploitable tooling with secrets or
   write access. Proposed initial targets: acknowledge critical within one
   business day and mitigate within 48 hours; high within two business days and
   fix within seven days; medium within 30 days; low within 90 days. Escalate
   blockers and agree an exposure-specific deadline rather than silently defer.
3. Assign a maintainer, perform a narrow compatible update and commit the
   generated lock. Run relevant unit/integration checks and package isolation.
   Avoid adding credentials to untrusted PR execution.
4. Record audit/test results and limitations in the PR. After merge, rerun the
   audits on master and verify Dependabot marks the matching alerts fixed.
   Shipped runtime findings also need the separately authorized release process.

There are currently no advisory exceptions. Any proposed exception must name
one GHSA/PKSA ID, exposure rationale, compensating control, named owner and an
expiry/review date, with maintainer approval recorded in a tracked issue. Review
it on each weekly failure; remove it or renew explicitly before expiry. Do not
introduce blanket or permanent package ignores.
