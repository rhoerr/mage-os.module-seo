Title: [Major] Test coverage gaps: 14/16 product builders, all controllers, most blocks untested; enforced mutation gate is 54, not the documented 75

**Severity: Major** (quality assurance)

The tests that exist are genuinely behavioral (420 unit tests / 66 files, plus integration DI-wiring and repository CRUD tests), but breadth is the gap — 135 classes in Model/Service/Observer/Controller/Block vs ~70 test files:

- Product builders: only **2 of 16** concrete builders unit-tested (Apparel, GenericProduct); Book, Food, Jewelry, Toy, etc. untested — and these are exactly where the invalid-schema-type/property bugs live.
- **Zero** controller tests (17 controllers, incl. 8 adminhtml CRUD).
- Blocks: 2 of 13 tested; Canonical, Hreflang, JsonLd, MetaTags untested.
- Routers: 1 of 3 (HreflangSitemapRouter, LlmsTxtRouter untested).
- Untested: all 3 PageTitle providers, 3 of 4 MetaTag providers, 4 of 8 StructuredData providers (incl. ProductSchemaProvider and BreadcrumbListProvider), Category/Cms repositories, LlmsTxtBuilder/JsonlBuilder, all Ui/ classes, 2 of 4 plugins.

`infection.json5` excludes this exact list from mutation testing (honestly documented) — the exclude list is a ready-made coverage worklist. Related inconsistency: `docs/progress.md` and `composer.json`'s `test:infection` script claim an "MSI ≥ 75" gate, but CI runs infection with no override, so the **effective CI gate is `minMsi: 54`**.

Also note: `Test/Integration/DiWiringTest.php` smoke-tests instantiation but doesn't assert pool contents — which is why the dead-DI-wiring blocker went undetected. Adding pool-content assertions would catch that whole bug class.

---
*Found during a code review assessing the module for potential Mage-OS bundling.*
