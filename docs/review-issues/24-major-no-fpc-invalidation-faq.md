Title: [Major] No IdentityInterface anywhere and no FPC invalidation on FAQ save — cached pages serve stale FAQ content indefinitely

**Severity: Major**

No block in the module implements `IdentityInterface` (zero hits outside tests), and `Controller/Adminhtml/Faq/Save.php` / `Delete.php` perform no cache handling. Editing or deleting a FAQ therefore leaves every cached page — the visible accordion *and* the FAQPage JSON-LD — stale until a manual full cache flush.

For comparison, `Controller/Adminhtml/Organisation/Save.php:140` at least invalidates the whole `full_page` cache type (a sledgehammer, and it also needlessly invalidates `config` — the data isn't in core_config_data — but it exists).

Related: the llms.txt/jsonl/hreflang-sitemap purge observers only purge when Varnish is configured (`Observer/InvalidateLlmsTxtCache.php:35-36` etc.), so built-in FPC serves those endpoints stale for up to `s-maxage` (24h) after catalog changes; and `llms.txt` content depends on Organisation data, FAQs, and support email, but nothing purges `RS_LLMS` on organisation/FAQ/config saves — only `catalog_category_save_after` (`etc/events.xml:10-15`).

**Suggested fix:** implement `IdentityInterface` on FAQ-rendering blocks with per-record identities (`mageos_seo_faq_<id>`), emit those tags, and purge them on FAQ save/delete; extend the observers to also clean the built-in `full_page` cache by tag; add organisation/FAQ save events to the llms.txt invalidation.

---
*Found during a code review assessing the module for potential Mage-OS bundling.*
