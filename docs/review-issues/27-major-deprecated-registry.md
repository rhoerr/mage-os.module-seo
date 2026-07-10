Title: [Major] Deprecated Magento\Framework\Registry used as the primary current-entity mechanism

**Severity: Major** (deprecated core API; inconsistent with the module's own better pattern)

`Magento\Framework\Registry` (deprecated since 102.0.0) is injected and used for `current_product`/`current_category` resolution in:

- `Model/StructuredData/Provider/ProductSchemaProvider.php:33,57`
- `Model/PageTitle/Provider/ProductTitleProvider.php:20,51`
- `Model/Hreflang/Resolver/ProductHreflangResolver.php:21,39`
- `Model/Hreflang/Resolver/CategoryHreflangResolver.php:21,39`
- `Model/MetaTag/Provider/ProductMetaProvider.php`

Meanwhile `Model/StructuredData/Provider/CategorySchemaProvider.php` correctly uses `LayerResolver` — the codebase knows the better pattern and is inconsistent.

**Suggested fix:** standardise on non-deprecated current-entity resolution (e.g. `Magento\Catalog\Helper\Data`-free approaches: `LayerResolver` for category, and a small shared current-product locator around the request/registry shim) so a future core removal of Registry doesn't break five providers at once.

Related minor: several classes type-hint the concrete `Magento\Framework\View\Layout` instead of `LayoutInterface` (`Model/StructuredData/Compositor.php:26`, `Model/PageTitle/Compositor.php`, `Model/Hreflang/ResolverPool.php:39`, `Model/RobotsMeta/Resolver.php:31`, `ArticleSchemaProvider.php:37`).

---
*Found during a code review assessing the module for potential Mage-OS bundling.*
