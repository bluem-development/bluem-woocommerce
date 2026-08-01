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
7. [ ] Extract support-report environment collection behind injectable WordPress and WooCommerce readers.
8. [ ] Extract support-report trace normalization into a pure trace formatter.
9. [x] Extract core plugin option definitions and option lookup into a settings object.
10. [x] Extract payment option lookup into a feature-specific settings object.
11. [ ] Extract mandate and iDIN settings access behind feature-specific settings objects.
12. [ ] Extract Bluem request persistence from `bluem-db.php` into a request repository.
13. [ ] Extract request logging and link persistence from `bluem-db.php` into focused repositories.
14. [ ] Extract payment callback status resolution and order-transition orchestration.
15. [ ] Extract iDIN validation and result handling into an identity workflow service.
16. [ ] Extract mandate request and callback orchestration into a mandate workflow service.
17. [ ] Extract Contact Form 7 and Gravity Forms flows into integration adapters.
18. [ ] Move admin request rendering and navigation behind presentation services.

The next increment is mandate and iDIN settings access.
