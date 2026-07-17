# Copilot Instructions for bluem-woocommerce

Use the top-level [AGENTS.md](../AGENTS.md) as the source of truth for this repository. It contains the current architecture notes, payment status handling, release checklist, SVN workflow, and packaging pitfalls.

## Quick Context

This is the developer repository for the Bluem WordPress/WooCommerce plugin. It provides Bluem ePayments, eMandates, iDIN identity services, and related integrations.

Current baseline:

- PHP requirement: `>=8.4` for this plugin.
- WordPress plugin entrypoint: `bluem.php`.
- WordPress.org readme/changelog: `readme.txt`.
- Developer readme: `README.md`.
- Release version source for Makefile flow: `build.env`.
- Composer dependency of special interest: `bluem-development/bluem-php`.
- Generated release package: `build/`.
- WordPress.org SVN working copy: `svn-directory/`.

## Important Guidance

- Do not edit `build/` or `svn-directory/` as source. They are generated/staged release artifacts.
- Do not run or suggest an SVN release commit unless the user explicitly asks for it.
- Treat `AGENTS.md` as authoritative when it conflicts with this file.
- For release/refactor planning, also read [docs/refactor-wishlist.md](../docs/refactor-wishlist.md).
- Be careful with Bluem payment statuses. `New`, `Open`, and `Pending` are in-progress states, not failures.
- Keep dependency changes synchronized between `composer.json` and `composer.lock`.

## Useful Checks

```bash
php -l bluem.php
php -l gateways/Bluem_Payment_Gateway.php
php -l gateways/Bluem_Bank_Based_Payment_Gateway.php
composer validate --no-check-publish
./vendor/bin/phpunit --testsuite Unit
git diff --check
```

