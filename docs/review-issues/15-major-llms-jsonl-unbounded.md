Title: [Major] /llms.jsonl builds the entire catalog in one request — unbounded memory plus per-product N+1 queries

**Severity: Major** (unusable beyond a few thousand SKUs; DoS surface when enabled)

`Model/LlmsJsonl/JsonlBuilder::build()` loads the whole enabled/visible product collection with `$collection->getItems()` — no page size, no streaming — then accumulates every line in an array and `implode`s (`Model/LlmsJsonl/JsonlBuilder.php:43-76`). On a 100k-product store this is a guaranteed multi-hundred-MB/OOM request.

It is also riddled with N+1s: `Model/LlmsJsonl/ProductLineBuilder.php:42` calls `$product->getProductUrl()` (a url-rewrite lookup per product — the collection never calls `addUrlRewrite()`), and line 54 calls `$product->isSalable()` (per-product salability/MSI resolution). That is roughly two extra queries per product.

Mitigations present: the feature is **off by default** (`etc/config.xml:21`) and the output contains only public catalog data (status/visibility filtered, `JsonlBuilder.php:44-50`; name/sku/300-char description/image/final price/salability only, `ProductLineBuilder.php:44-74`). But once enabled it is public with no throttle and cache misses are attacker-forceable via query strings (separate issue).

**Suggested fix:** pre-generate the feed to a file via cron (like core sitemap) or paginate the collection with `addUrlRewrite()` and batch salability; serve the artifact statically.

---
*Found during a code review assessing the module for potential Mage-OS bundling.*
