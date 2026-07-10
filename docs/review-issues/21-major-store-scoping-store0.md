Title: [Major] Per-store category/product SEO overrides read/write store 0 in adminhtml — the multistore override feature is unreachable from the UI

**Severity: Major** (multistore correctness)

Both save plugins and both form modifiers resolve the store as `(int) $this->storeManager->getStore()->getId()`:

- `Plugin/Catalog/Category/SaveSeoConfigPlugin.php:93`
- `Plugin/Catalog/Product/SaveSeoOverridesPlugin.php:51`
- `Ui/DataProvider/Category/Form/Modifier/SeoModifier.php:139`
- `Ui/DataProvider/Product/Form/Modifier/SeoModifier.php:130`

In the adminhtml area, the current store is the admin store (ID 0) unless explicitly switched — core admin catalog controllers pass the store view as the `store` request parameter and do not set the current store. Net effect: per-store SEO overrides (the point of the `store_id` column and the merge logic in `Model/Category/ConfigRepository::loadRow()`, lines 88-122) are always read and written as store 0, regardless of the store-view switcher in the product/category form.

Related: the category form modifier passes an empty `$categoryPath` (`SeoModifier.php:140` category variant), so the documented ancestor-inheritance display (`ConfigRepository::getForCategory():53-71`) never shows inherited values — the form shows blank while the frontend inherits.

**Suggested fix:** read `(int) $request->getParam('store', 0)` like core admin catalog does, in all four classes; populate the category path for inheritance display.

---
*Found during a code review assessing the module for potential Mage-OS bundling.*
