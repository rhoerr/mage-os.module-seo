Title: [Naming] Rename rs_seo / RS_* / makers_* leftovers and hyphenated table names before first release — they become public contract once shipped

**Severity: Minor individually, high strategic priority** (breaking to change after release)

The module mixes `MageOS_Seo`/`mageos_seo_*` naming with leftovers from its pre-donation origin project:

- **Routes/frontNames**: `rs_seo`/`rs-seo` (`etc/frontend/routes.xml:5`, `etc/adminhtml/routes.xml:5`; routers hardcode `'rs-seo'` at `Model/Router/LlmsTxtRouter.php:21-23,51`). Admin route names are user-visible and semi-permanent (bookmarks, ACL logs, integrations).
- **Layout/block names**: `rs_seo.*` blocks (`view/frontend/layout/default.xml:13-37`), admin layout files `rs_seo_faq_edit.xml`, `rs_seo_faq_index.xml`, `rs_seo_organisation_edit.xml`, form namespace `rs_seo`.
- **DI/observer/plugin names**: `rs_seo_*` (`etc/di.xml:322-338`, `etc/frontend/di.xml:7,16-29`, `etc/events.xml`).
- **Cache tags**: `RS_LLMS`, `RS_LLMS_FULL`, `RS_HREFLANG_SITEMAP`, `RS_LLMS_JSONL` (`Observer/InvalidateLlmsTxtCache.php:41-42`, `Controller/Llms/Index.php:47`, `Controller/Hreflangsitemap/Index.php:56`) — these leak as `X-Magento-Tags` response headers and are Varnish-purge contract.
- **POST field names**: `rs_seo_*` in the category/product form modifiers and save plugins.
- **DB**: hyphenated table names `mage-os_seo_faq`, `mage-os_seo_organisation`, `mage-os_seo_category_config`, `mage-os_seo_product_override` (`etc/db_schema.xml:6,44,69,91`) — legal only with identifier quoting, contrary to ecosystem convention (`mageos_seo_*`), and hostile to raw SQL/reporting tooling. Constraint/index referenceIds use `RS_SEO_*` (`db_schema.xml:37,62,81,107`).
- **Origin artifacts**: hardcoded `makers_*` layout handles (`Model/StructuredData/Provider/BreadcrumbListProvider.php:29-33`), `GBP` fallback currency (`Service/CurrencyService.php:47`).
- **Spelling mix**: `Organisation` (Api/Data, Model) vs `Organization` (StructuredData provider) — confusing for contributors.

Renaming any of these after a shipped release means breaking migrations (db_schema rename columns/tables, route redirects, cache-tag transitions). Doing it now costs almost nothing.

**Suggested fix:** one consolidated rename pass to `mageos_seo` / `MAGEOS_SEO_*` / `mageos_seo_*` tables (with a declarative-schema rename via `onCreate="migrateDataFromAnotherTable"` for any existing installs), remove `makers_*` and `GBP` leftovers, and settle the Organisation/Organization spelling.

---
*Found during a code review assessing the module for potential Mage-OS bundling.*
