Title: [Major] FAQ widget ttl=86400 defeats the collector — FAQPage JSON-LD silently disappears under block cache

**Severity: Major** (silent loss of the feature's core promise)

The FAQ collector registration is a render-time side effect: `Block/AbstractFaqElement.php:82` calls `$this->collector->collect($identifier)` inside `getFaqs()` from `_toHtml()`. But `etc/widget.xml:7` sets `ttl="86400"` on the FaqList widget, so its HTML is block-cached. When a page is re-rendered on an FPC miss while the widget block cache is warm, `toHtml()` returns the cached HTML without invoking `_toHtml()` — `collect()` never runs — and the late `FaqJsonLd` block (`view/frontend/layout/default.xml:35-39`) emits **nothing**. The page is then stored in FPC/Varnish **without FAQPage JSON-LD** for up to 24h.

The module's own design doc names exactly this hazard (`docs/planned-features/faq.md:113-122`), but the shipped mitigation (store identifiers, re-resolve late in `Block/FaqJsonLd.php`) only fixes staleness, not the lost `collect()` call. The README's "block-cache-immune" claim is therefore wrong (`Block/FaqJsonLd.php:18-20`, `Api/FaqCollectorInterface.php:12-14` docblocks state the inverted reasoning).

**Suggested fix:** remove the widget `ttl` (FPC already caches the page), or resolve FAQ identifiers from page context (layout/widget instance data) instead of render-time collection.

---
*Found during a code review assessing the module for potential Mage-OS bundling.*
