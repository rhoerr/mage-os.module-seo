Title: [Major] Product schema templates emit non-existent or non-Product schema.org types, forfeiting rich-result eligibility

**Severity: Major**

Two builders emit types that do not exist in the schema.org vocabulary:

- `Model/Product/Builder/ApparelBuilder.php:133` — `@type: "Apparel"`
- `Model/Product/Builder/FoodBuilder.php:133` — `@type: "FoodProduct"`

Pages using these templates get zero Product rich-result eligibility and "unrecognized type" errors in validators.

Additionally, `BookBuilder` (`Book`), `ArtAndCraftBuilder` (`VisualArtwork`), and `SoftwareBuilder` (`SoftwareApplication`) are valid schema.org types but are **CreativeWork subtypes, not Product** — Google's Product snippets / merchant listings require `Product` (or a multi-type array such as `["Product","Book"]`). As written, choosing any of these five templates removes the product from Product rich results entirely.

**Suggested fix:** use `@type: "Product"` (or multi-type arrays like `["Product","Book"]`) for all templates; express category-specific semantics via `additionalProperty`/category-appropriate Product properties instead of invented types.

---
*Found during a code review assessing the module for potential Mage-OS bundling.*
