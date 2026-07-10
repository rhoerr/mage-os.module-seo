Title: [Major] JSON-LD and OG prices are emitted in base currency but labelled with the display currency

**Severity: Major** (numerically wrong prices on any multi-currency store)

`Model/Product/Builder/AbstractBuilder.php:217-225` uses `getPriceInfo()->getPrice('final_price')->getValue()` — a **base-currency** amount — and pairs it with `CurrencyService::getCurrentCurrencyCode()` (`AbstractBuilder.php:82`, `Service/CurrencyService.php:28-35`), which returns the customer's **display** currency. On any store where display currency ≠ base currency, the JSON-LD price is wrong (e.g. a EUR amount labelled `"priceCurrency": "USD"`).

The service already provides `convertFromBase()` (`Service/CurrencyService.php:141`) but the builders never call it.

Same bug in:
- `Model/MetaTag/Provider/ProductMetaProvider.php:85-92` (`product:price:amount`)
- `AbstractBuilder.php:165-166` — AggregateOffer `lowPrice`/`highPrice`

Related nit: `CurrencyService.php:47` falls back to `GBP` — a leftover from the module's origin project; the store's base currency would be a saner fallback.

**Suggested fix:** convert amounts with `CurrencyService::convertFromBase()` before emitting, or emit base currency amounts with the base currency code consistently.

---
*Found during a code review assessing the module for potential Mage-OS bundling.*
