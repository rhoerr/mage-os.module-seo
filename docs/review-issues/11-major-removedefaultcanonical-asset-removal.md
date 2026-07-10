Title: [Major] CanonicalUrlManager::removeDefaultCanonical() can remove arbitrary CSS/JS page assets

**Severity: Major**

`Model/Canonical/CanonicalUrlManager.php:49-58` removes every page asset whose identifier matches `#/{url_key}(\.[a-z]{1,5})?$#`. The page-asset collection contains **all** CSS/JS/images, not just canonical link assets — so a product with url_key `print` removes `css/print.css`; url_key `main` removes `js/main.js`.

Mitigating factor: nothing inside this module currently calls `setCanonical()` — the manager exists for bridge modules (see the dead-code issue) — but any consumer would inherit this bug.

**Suggested fix:** iterate only assets whose content type is `canonical` (the asset API exposes it) instead of regex-matching identifiers.

---
*Found during a code review assessing the module for potential Mage-OS bundling.*
