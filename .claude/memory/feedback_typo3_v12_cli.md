---
name: TYPO3 v12 CLI specifics
description: Verified TYPO3 v12.4 CLI commands and Composer-mode behavior for DDEV setup
type: feedback
---

TYPO3 v12.4 Composer-mode CLI facts:

- `typo3 setup` uses `--admin-user-password` (NOT `--admin-password`)
- `extension:setup` calls `dumpclassloadinginformation` which does NOT exist in Composer-mode — use `database:updateschema` instead
- In Composer-mode all extensions in vendor are automatically active — no manual activation needed
- QA tool versions for v12: phpstan ^1.10, saschaegerer/phpstan-typo3 ^1.9, ssch/typo3-rector ^1.0, phpunit ^10.5, testing-framework ^8.0

**Why:** Wrong versions and CLI flags caused three failed `ddev typo3-setup` runs.

**How to apply:** Always cross-check CLI flags and dependency versions against the specific TYPO3 major version in use.
