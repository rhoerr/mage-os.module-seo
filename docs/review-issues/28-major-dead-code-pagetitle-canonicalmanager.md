Title: [Major] PageTitle subsystem and CanonicalUrlManager are dead code shipped as features

**Severity: Major** (advertised extension points with zero runtime effect)

1. **PageTitle**: `Model/PageTitle/Compositor.php` + three providers are wired in `etc/di.xml:128-147` and documented as an extension point (`docs/extending.md:13,108`), but **nothing in the module ever calls `Compositor::getTitle()`** — no block, observer, or plugin. The feature has zero runtime effect. Worse, if a consumer were added as-is, `Model/PageTitle/Provider/ProductTitleProvider.php:52` returns `$product->getName()`, which would clobber the merchant's `meta_title` attribute — the providers ignore product/category/CMS `meta_title` entirely.
2. **CanonicalUrlManager**: `Model/Canonical/CanonicalUrlManager.php` has zero production callers in this module (only its unit test). The comment in `Block/Canonical.php:12-14` ("product and category pages manage their own canonicals via CanonicalUrlManager") refers to sibling modules (e.g. MageOS_ProductVariantUrl) that are not part of this package.

**Suggested fix:** either wire the PageTitle compositor into `Page\Config\Title` correctly (preferring `meta_title` over name) or remove the subsystem; either document CanonicalUrlManager as a bridge-only API or move it to the module that consumes it. Note it also carries its own asset-removal bug (separate issue).

---
*Found during a code review assessing the module for potential Mage-OS bundling.*
