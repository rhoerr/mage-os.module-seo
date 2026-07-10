Title: [Blocker] Default INDEX,FOLLOW robots meta overrides core design robots config — installing the module re-opens noindexed sites

**Severity: Blocker** (dangerous default for a bundled module)

`etc/config.xml:24-26` ships `product_default` / `category_default` / `cms_page_default` = `INDEX,FOLLOW`, and the providers fall back to these when no per-entity override exists (`Model/RobotsMeta/Provider/ProductRobotsProvider.php:50-54`, `CategoryRobotsProvider.php:55-59`, `CmsPageRobotsProvider.php:47-49`). `Observer/ApplyRobotsMeta.php:47` then calls `$pageConfig->setRobots()` on **every** catalog/CMS page.

**Impact:** Magento core's `design/search_engine_robots/default_robots` — the standard way merchants keep staging/dev environments at `NOINDEX,NOFOLLOW` — is silently overridden back to `INDEX,FOLLOW` on all product, category, and CMS pages the moment this module is installed and enabled. A staging site configured NOINDEX would be re-opened to indexing.

**Suggested fix:** ship the defaults empty ("no opinion") so the providers return nothing and core behaviour is preserved unless the merchant explicitly configures values. Alternatively, have the providers respect core's default_robots when it is more restrictive.

Also note (doc nit): `Model/RobotsMeta/Resolver.php:11-17` says "first-wins" while the implementation is highest-sortOrder-wins (line 66).

---
*Found during a code review assessing the module for potential Mage-OS bundling.*
