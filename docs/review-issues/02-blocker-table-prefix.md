Title: [Blocker] Table names resolved via adapter getTableName() — module breaks on any install with a DB table prefix

**Severity: Blocker**

All direct-SQL repositories resolve table names by calling `getTableName()` on the **DB adapter**, which only shortens long identifiers — it does **not** apply the configured DB table prefix (see `Magento\Framework\DB\Adapter\Pdo\Mysql::getTableName()`). `ResourceConnection::getTableName()` is the correct call; the class is even injected in these files and then bypassed.

Affected:
- `Model/Faq/Repository.php:40`
- `Model/Category/ConfigRepository.php:90,134`
- `Model/Category/ProductOverrideRepository.php:43,83`
- `Model/Review/NativeAggregateRatingProvider.php:37`
- `Model/Hreflang/UrlRewriteFetcher.php:36,75`

**Impact:** on any installation configured with a `table_prefix`, declarative schema creates the tables *with* the prefix but these queries hit the unprefixed names → SQL "table not found" exceptions across the frontend FAQ path, category/product SEO config, aggregate ratings, and hreflang. Combined with the missing try/catch in the catalog save plugins (separate issue), every product/category save with SEO data would error on prefixed installs.

**Suggested fix:** replace all `$connection->getTableName(...)` calls with `$this->resourceConnection->getTableName(...)`.

---
*Found during a code review assessing the module for potential Mage-OS bundling.*
