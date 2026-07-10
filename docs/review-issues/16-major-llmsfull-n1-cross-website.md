Title: [Major] /llms-full.txt category tree: COUNT query per category (N+1) and no store scoping — leaks categories from other websites

**Severity: Major** (performance + information disclosure; endpoint default ON)

Two problems in `Model/LlmsTxt/LlmsTxtBuilder`:

1. **N+1 COUNT queries.** `product_count` is not an EAV attribute, so `addAttributeToSelect([... 'product_count'])` (`LlmsTxtBuilder.php:217`) loads nothing and `$category->getProductCount()` at line 227 lazily fires one `COUNT` query per category. Thousands of categories = thousands of queries on every cache miss of a default-ON endpoint (`etc/config.xml:19-20`).
2. **No store scoping.** The category collection has no `setStoreId()` and no root-path filter (`LlmsTxtBuilder.php:216-220`), so it emits categories from **every website/store group** — including hidden B2B/staging websites — and pairs their `url_path` with the *current* store's base URL (line 225), producing both an information disclosure and broken URLs. `is_active` is evaluated per-row at default scope, so children of disabled subtrees still appear.

Related minor: category URLs omit the configured URL suffix, and the search URL is hardcoded to `/catalogsearch/result?q=`.

**Suggested fix:** scope the collection to the current store's root category path and store ID, filter by scoped `is_active` walking the tree, and use `Collection::loadProductCount`-style bulk loading (or drop counts).

---
*Found during a code review assessing the module for potential Mage-OS bundling.*
