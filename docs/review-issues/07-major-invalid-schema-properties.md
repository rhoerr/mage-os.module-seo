Title: [Major] Several product schema builders emit properties invalid for their declared type

**Severity: Major** (Search Console / validator warnings)

- `Model/Product/Builder/FoodBuilder.php:78-121` — `nutritionInformation`, `containsAllergen`, `isAlcoholicBeverage` are not Product properties (they belong to MenuItem/Recipe/DietarySupplement contexts).
- `Model/Product/Builder/PetBuilder.php:70,79` — `nutritionInformation` on Product; pet species stuffed into `PeopleAudience.suggestedGender`, which is semantically wrong.
- `Model/Product/Builder/ToyBuilder.php:94` — `batteriesRequired` is not a schema.org property at all.
- `Model/Product/Builder/LocalExperienceBuilder.php:66-79` — `location`, `duration`, `organizer` on `@type: Product` (these are Event properties).
- `Model/Product/Builder/ApparelBuilder.php:79,91` — `color`/`size` placed on the **Offer** node; schema.org defines them on Product.
- `Model/Product/Builder/BookBuilder.php:61,66`, `ArtAndCraftBuilder.php:92`, `SoftwareBuilder` — `gtin13` on CreativeWork types where it is not defined.

**Suggested fix:** audit each template against the schema.org Product property set; move category-specific data into valid properties (`additionalProperty`, `audience`, `material`, etc.) and keep offers limited to Offer properties.

---
*Found during a code review assessing the module for potential Mage-OS bundling.*
