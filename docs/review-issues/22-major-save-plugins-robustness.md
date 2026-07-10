Title: [Major] Catalog save plugins: global-area registration, raw POST sniffing, and no try/catch — a SEO-table failure aborts product/category saves post-commit

**Severity: Major**

`Plugin/Catalog/Category/SaveSeoConfigPlugin.php` and `Plugin/Catalog/Product/SaveSeoOverridesPlugin.php` are `afterSave` interceptors on `CategoryRepositoryInterface`/`ProductRepositoryInterface` with three problems:

1. **Registered in global `etc/di.xml:328-339`**, not `etc/adminhtml/di.xml`, so they execute on every repository save in all areas — REST, GraphQL, imports, cron. They no-op only because non-admin requests lack the `rs_seo_*` POST keys, but a REST payload could inject those keys, and request-sniffing in the persistence layer is area-inappropriate.
2. **No try/catch around the SEO persistence** (`SaveSeoConfigPlugin.php:92-95`, `SaveSeoOverridesPlugin.php:68-70`). The plugin runs after the entity is committed: a duplicate-key/SQL failure (e.g. the table-prefix blocker) throws out of the save — admin sees "save failed" for an entity that actually saved. Combined with the table-prefix bug, every product save with the SEO tab errors on prefixed installs.
3. **Silent data loss on bad input:** `rs_seo_override_fields` JSON is decoded and silently coerced to `[]` on invalid JSON (`SaveSeoConfigPlugin.php:82-90`, `SaveSeoOverridesPlugin.php:54-61`) — an admin typo wipes existing overrides with no feedback, and there is no client-side JSON validation on the textarea.

**Suggested fix:** move registration to `etc/adminhtml/di.xml`; wrap persistence in try/catch with logging + admin message; on invalid JSON, keep existing values and surface a validation error. Longer term, consider extension attributes or handling in the admin controller/DataProvider instead of POST sniffing.

---
*Found during a code review assessing the module for potential Mage-OS bundling.*
