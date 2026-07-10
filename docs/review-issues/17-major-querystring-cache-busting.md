Title: [Major] Query-string cache-busting enables regeneration DoS on all generated endpoints

**Severity: Major**

The custom routers match on path only (`Model/Router/LlmsTxtRouter.php:43-47`, `WellKnownRouter.php:42-55`, `HreflangSitemapRouter.php:38`), but query strings ARE part of the FPC/Varnish cache key. So `GET /llms.jsonl?x=1`, `?x=2`, … each miss the cache and each trigger a full rebuild — the same applies to `/llms-full.txt` and `/hreflang-sitemap.xml`, whose builders do whole-catalog work (see companion issues).

An attacker gets a cheap amplification loop against the expensive builders; there is no rate limit, no query-string normalisation, and no pre-generated artifact.

**Suggested fix:** pre-generate to files via cron and serve statically (resolves this together with the builder-scale issues). Short of that, document/require a Varnish/CDN rule stripping query strings on these paths, or have the controllers redirect non-empty query strings to the canonical path.

Related nit: the controllers are also reachable at their standard-router URLs (`/rs-seo/llmsjsonl/index` etc., `etc/frontend/routes.xml:5`), creating duplicate cache entries and duplicate-content URLs.

---
*Found during a code review assessing the module for potential Mage-OS bundling.*
