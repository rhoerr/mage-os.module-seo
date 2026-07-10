Title: [Major] composer.json missing hard requires for ~8 Magento modules used in always-loaded code

**Severity: Major** (installability/compile correctness)

`composer.json` requires only framework/catalog/store/cms/robots, but code that is constructor-injected or class-referenced (needed for `setup:di:compile` and at runtime) hard-depends on:

- `magento/module-catalog-inventory` — `Model/Product/Builder/AbstractBuilder.php:9,49` (`StockRegistryInterface`; itself legacy/MSI-discouraged — consider `IsProductSalableInterface`)
- `magento/module-page-cache` — `Observer/InvalidateLlmsTxtCache.php:10`, `InvalidateLlmsJsonlCache.php:10`, `InvalidateHreflangSitemapCache.php:10`
- `magento/module-cache-invalidate` — `Observer/*.php` (`PurgeCache`); currently listed only in **require-dev**, which shows the gap was noticed but not fixed
- `magento/module-widget` — `Block/Widget/FaqList.php:7` + `etc/widget.xml` (in module.xml sequence but not composer)
- `magento/module-media-storage` — `Controller/Adminhtml/Organisation/UploadLogo.php:13`
- `magento/module-backend`, `magento/module-ui` — all `Controller/Adminhtml/*`, UI components, `Ui/DataProvider/*`
- `magento/module-directory` — `Service/CurrencyService.php:7`
- `magento/module-config` — `Magento\Config\Model\Config\Backend\Encrypted` (`etc/adminhtml/system.xml:322`)

By contrast, PageBuilder is handled **correctly** as a soft dependency (sequence-only, no PHP type references, `str_contains`-guarded plugin) — that pattern just needs to be matched by accurate hard requires for the rest. Note also `Magento_Review` is a soft dep via raw SQL with a hardcoded `entity_type = 1` (`Model/Review/NativeAggregateRatingProvider.php:21,44`) that throws uncaught if the review tables are absent.

**Suggested fix:** add the missing requires (and move `module-cache-invalidate` out of require-dev or make the purge publisher optional); add a `license` field while touching the file.

---
*Found during a code review assessing the module for potential Mage-OS bundling.*
