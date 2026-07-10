Title: [Major] Hreflang emits duplicate hreflang values when multiple stores share a locale

**Severity: Major** (invalid hreflang flagged by Google)

`Model/Hreflang/StoreLocaleMap.php:51` iterates **all stores of all websites**, and `Model/Hreflang/AlternateBuilder.php:34` only bails when there are *fewer than two distinct* locales. With three stores (`en-US`, `en-US`, `de-DE`) — a very common multi-website or B2B/B2C setup — the output contains **two `hreflang="en-US"` entries pointing at different URLs**, which is invalid.

There is no per-hreflang-value dedupe and no same-website scoping.

**Suggested fix:** scope the store set (e.g. per website, or a configurable store group) and/or deduplicate by hreflang value with a deterministic winner; document the intended multi-website behaviour.

---
*Found during a code review assessing the module for potential Mage-OS bundling.*
