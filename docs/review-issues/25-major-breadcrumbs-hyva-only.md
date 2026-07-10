Title: [Major] BreadcrumbList provider is Hyvä-only (silently absent on Luma) and contains hardcoded handles from the origin project

**Severity: Major**

`Model/StructuredData/Provider/BreadcrumbListProvider.php:55-62` requires a `getCrumbs()` method on the `breadcrumbs` block. Hyvä's breadcrumb block has it; core Luma `Magento\Theme\Block\Html\Breadcrumbs` does not (only `addCrumb`/`_toHtml`), so on default themes the `method_exists` check fails and **the advertised BreadcrumbList schema never renders, silently**.

Additionally, lines 29-33 hardcode `EXCLUDED_HANDLES = ['makers_profile_view', 'makers_index_index', 'makers_enroll_index']` — layout handles from the original author's marketplace project that have no place in a generic module.

**Suggested fix:** add a Luma-compatible crumb source (e.g. rebuild crumbs from the catalog data layer / current entity path, or read the block's internal crumbs via a shared abstraction), and remove the `makers_*` handles (make exclusions DI-configurable).

---
*Found during a code review assessing the module for potential Mage-OS bundling.*
