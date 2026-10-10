# Refactor increments

This is the ordered roadmap for moving Bluem's procedural plugin surface into
small, namespaced objects while keeping the existing WordPress-facing
functions as compatibility adapters during the migration.

1. [x] Extract iDIN age calculation into `BluemAgeCalculator`.
2. [x] Extract request date formatting into `BluemDateFormatter`.
3. [x] Extract request-type labeling into `BluemRequestTypeLabeler`.
4. [x] Extract request grouping into `BluemRequestGrouper`.
5. [x] Extract enabled request-type filtering into `BluemEnabledRequestTypeFilter`.
6. [x] Extract Composer dependency version lookup into a testable support service.
7. [x] Extract support-report environment collection behind injectable WordPress and WooCommerce readers.
8. [x] Extract support-report trace normalization into a pure trace formatter.
9. [x] Extract core plugin option lookup into a settings object.
10. [x] Extract payment option lookup into a feature-specific settings object.
11. [x] Extract mandate option lookup into a feature-specific settings object.
12. [ ] Extract iDIN settings access into a feature-specific settings object.
13. [ ] Extract Bluem request persistence from `bluem-db.php` into a request repository.
14. [ ] Extract request logging and link persistence from `bluem-db.php` into focused repositories.
15. [ ] Extract payment callback status resolution and order-transition orchestration.
16. [ ] Extract iDIN validation and result handling into an identity workflow service.
17. [ ] Extract mandate request and callback orchestration into a mandate workflow service.
18. [ ] Extract Contact Form 7 and Gravity Forms flows into integration adapters.
19. [ ] Move admin request rendering and navigation behind presentation services.

## Current three-batch plan

1. [x] Core settings schema and configuration assembly: extract `BluemCoreOptions`
   and the shared `BluemConfigurationBuilder`, preserving existing wrappers,
   defaults, translation extraction, and feature merge order. Module status is
   already covered by open PR #68 and is excluded from this batch.
2. [ ] Request lookup repository: move reads/filtering/order selection behind an
   injectable database dependency. Leave writes, logging, and cookie storage
   for separate work.
3. [ ] Payment transition services: move the existing gateway decisions and order
   update orchestration into reusable services, retaining gateway adapters.

Each batch branches from master and owns separate methods/files. If future work
requires an unmerged batch, explicitly label it as a dependent sub-batch.

### Shared service boundaries

`BluemConfigurationBuilder` accepts any ordered feature definitions; it has no
dependency on a gateway, WordPress, or a fixed module list. WordPress adapters
collect translated labels, options, and available providers. Definition lookup
and saved-value resolution remain separate contracts.

Follow the same approach for request lookup: share the repository across admin,
payment, identity, and mandate workflows through composition and injected
dependencies. Keep workflow-specific decisions out of the repository. Add
interfaces where consumers need substitutable implementations, rather than
introducing a generic base class or duplicating shared logic per feature.
