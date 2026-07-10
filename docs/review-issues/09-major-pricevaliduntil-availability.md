Title: [Major] priceValidUntil is always fabricated ("today + N months"), ignoring real special-price end dates; availability mapping gaps

**Severity: Major** (incorrect commitments to Google)

`Model/Product/Builder/AbstractBuilder.php:98,298-303` always emits `priceValidUntil` as "today + N months" (default 12, `etc/config.xml:15`) regardless of any actual `special_to_date`. On sale prices that end sooner, the JSON-LD promises a price beyond its real validity — a data-quality problem for Merchant listings.

Related issues in the same offer node:
- `AbstractBuilder.php:97` hardcodes `itemCondition: NewCondition` while `ItemConditionEnricher` re-adds it from config (`Model/Product/OfferEnricher/ItemConditionEnricher.php:30-43`). If the merchant clears the config, the hardcoded NewCondition remains — no way for used-goods stores to remove it short of overriding the builder.
- `AbstractBuilder.php:234-247` — availability only ever maps to `InStock`/`OutOfStock`; the `AVAILABILITY_PREORDER` constant (line 23) is dead code and backorders map to InStock.
- `AbstractBuilder.php:302` uses `date()`/`strtotime()` — ignores store timezone; core standard is `TimezoneInterface`.

**Suggested fix:** use `special_to_date` when present and omit `priceValidUntil` otherwise (or make the synthetic date opt-in); remove the hardcoded itemCondition in favour of the enricher; map backorders/preorders to the correct availability values via a testable mapping.

---
*Found during a code review assessing the module for potential Mage-OS bundling.*
