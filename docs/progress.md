# Implementation Progress — SEO / AEO / GEO Roadmap

Tracks delivery against [`planned-features/_roadmap.md`](planned-features/_roadmap.md). Gate suite per
[`.github/workflows/ci.yml`](../.github/workflows/ci.yml): phpcs, php-cs-fixer, PHPStan, PHPUnit unit,
integration (CI), infection MSI ≥ 54 enforced (CI; 75 is the target as builder/controller coverage grows), `setup:di:compile` (CI).

Legend: ✅ done & green · 🟡 in progress · ⏳ pending · CI = validated only in CI (no full Magento /
coverage driver locally).

## Phase 0 — Foundation & quick wins

### 0a — Pool kernel ✅
- `Model/Pool/HandleMatcher.php` — single shared handle-matching collaborator.
- Refactored `Model/StructuredData/Compositor`, `Model/MetaTag/Compositor`,
  `Model/PageTitle/Compositor` to delegate to it via an optional null-defaulted constructor arg
  (DI injects in prod; existing tests untouched).
- Tests: `Test/Unit/Model/Pool/HandleMatcherTest` (7 cases).

### 0b — Robots meta pool ✅
- `Api/RobotsMetaProviderInterface` + `Model/RobotsMeta/Resolver` (first-wins by sortOrder).
- Providers: `ProductRobotsProvider`, `CategoryRobotsProvider`, `CmsPageRobotsProvider`
  (CMS robots gap closed).
- Single applier `Observer/ApplyRobotsMeta` on `layout_generate_blocks_after`
  (`etc/frontend/events.xml`) — covers any registered page type with no per-controller plugin.
- Removed `Plugin/Controller/{Product,Category}RobotsMetaPlugin` + their di.xml bindings; pool wired
  in `etc/di.xml`.
- `Config::getRobotsCmsDefault()` + `XML_ROBOTS_CMS_DEFAULT`; `cms_page_default` in
  `etc/config.xml` + `etc/adminhtml/system.xml`; enriched `Model/Config/Source/RobotsMeta`
  (`max-snippet`, `max-image-preview`, `noarchive`, `noai`, `noimageai`).
- Tests: Resolver (10), 3 provider tests, `ApplyRobotsMetaTest`, updated `RobotsMetaTest`,
  + `DiWiringTest::testRobotsMetaResolverIsInstantiableViaDi`.

**Design note:** robots applier is an observer (not per-controller plugins) so bridge modules add a
provider and it applies automatically. This exact hook is fully exercised only by CI integration tests.

### 0d — Pagination robots ✅
- `Model/RobotsMeta/Provider/CatalogPaginationRobotsProvider` (sortOrder 200, wins over category
  default on `?p>1`). Opt-in via `mageos_seo_general/robots_meta/paginated_enabled` (default off) +
  `paginated_robots` (default NOINDEX,FOLLOW). Canonicalisation left to Magento's native category
  canonical tag (documented). `Config::isPaginatedRobotsEnabled()` / `getRobotsPaginated()`.
  Test: `CatalogPaginationRobotsProviderTest`. (Closes the deferred 0c pagination item via the
  robots pool rather than a canonical override.)

### 0c — On-page foundation 🟡 (mostly done; pagination delivered separately above)
- ✅ `@id` foundation: new `Model/StructuredData/OrganisationId` (single source of
  `{orgUrl}/#organization`). `OrganisationProvider` migrated to it via an optional null-defaulted
  ctor arg — output identical, existing `OrganisationProviderTest` untouched and green. Ready for the
  Phase 3 consumers (WebSite/LocalBusiness/Event/BlogPosting). Test: `OrganisationIdTest`.
- ✅ OG/Twitter (site-level): new `Model/MetaTag/Provider/SiteMetaProvider` (`['*']`) emits
  `og:site_name`, `og:locale`, `twitter:card=summary_large_image`. Wired into the MetaTag compositor.
  Test: `SiteMetaProviderTest`. (Twitter title/description/image fall back to the existing per-page
  `og:*`, so the card is complete without per-page duplication.)
- ⏳ Polish deferred: `og:image:width/height` and per-page `twitter:image` mirrors (Twitter card is
  already complete via og fallback).

## Phase 1 — Product / Offer richness ✅

### Reviews — AggregateRating priority pool ✅
- `Api/AggregateRatingProviderInterface` + `Model/Review/AggregateRatingResolver` (priority pool) +
  `Model/Review/NativeAggregateRatingProvider` (native `review_entity_summary`, low-priority fallback,
  null when zero reviews). Bridge review vendors register a higher-priority provider via di.xml.
- Config toggle `mageos_seo_general/structured_data/aggregate_rating_enabled` (default on) +
  `Config::isAggregateRatingEnabled()`.
- Emitted at product level from `AbstractBuilder::buildBase()`.
- Tests: `AggregateRatingResolverTest`, `NativeAggregateRatingProviderTest`, builder integration.

### Merchant policies — Offer enricher pool ✅
- `Api/OfferEnricherInterface` + `Model/Product/OfferEnricher/Pool` (collect-all, sortOrder merge).
- Built-in enrichers: `ItemConditionEnricher`, `ReturnPolicyEnricher` (hasMerchantReturnPolicy),
  `ShippingDetailsEnricher` (OfferShippingDetails, single domestic zone). Opt-in (return/shipping
  default off). Currency follows store display currency.
- New `mageos_seo_merchant` config section + source models: `ItemCondition`, `ReturnFees`,
  `RefundType`, `ReturnMethod`, `ReturnPolicyCategory` (admin label → schema.org enum URL).
- Merged into the Offer node in `AbstractBuilder::buildBase()`.
- Tests: `PoolTest`, `ItemConditionEnricherTest`, `ReturnPolicyEnricherTest`,
  `ShippingDetailsEnricherTest`, 5 source-model tests.

### AggregateOffer for configurables ✅
- `AbstractBuilder::resolvePriceRange()` + `buildAggregateOffer()`: configurable products with a
  usable min/max final price emit an `AggregateOffer` (lowPrice/highPrice) instead of a single Offer;
  guarded (single-variant requests and any unusable range keep the single Offer).
- Tests in `AbstractBuilderEnrichmentTest`.

**Integration:** `AbstractBuilder` gained the OfferEnricher pool + AggregateRatingResolver as optional
null-defaulted ctor args, so the 16 builders' existing tests are untouched (no-op when absent).
DI smoke tests added in `DiWiringTest`.

## Phase 2 — Multistore SEO (hreflang) ✅

### Deliverable 1 — `<head>` hreflang tags ✅
- `Api/HreflangResolverInterface` + `Model/Hreflang/ResolverPool` (picks first matching resolver,
  appends automatic language-only tags + configured x-default, returns nothing for single-store
  pages). Reuses the shared `HandleMatcher`.
- `Model/Hreflang/StoreLocaleMap` (active/eligible stores → base URL + BCP 47 locale, excludes
  inactive/configured-excluded), `UrlRewriteFetcher` (canonical url_rewrite rows per store),
  `LinkBuilder` (rewrites + map → absolute alternate links).
- Resolvers: `ProductHreflangResolver`, `CategoryHreflangResolver`, `CmsPageHreflangResolver`
  (home page uses store base URLs directly). Bridge page types add a resolver via di.xml.
- `Block/Hreflang` + `seo/hreflang.phtml` injected into `head.additional` (after canonical),
  FPC-safe.
- Config group `hreflang` (enabled, language_only_enabled, xdefault_store_id, excluded_store_ids) +
  `Config` getters + `Model/Config/Source/StoreViews`.
- Tests: `ResolverPoolTest` (language-only/x-default/single-store/precedence), `StoreLocaleMapTest`,
  `UrlRewriteFetcherTest`, `LinkBuilderTest`, 3 resolver tests, `StoreViewsTest`, DI smoke test.

### Deliverable 2 — `/hreflang-sitemap.xml` ✅
- Shared `Model/Hreflang/AlternateBuilder` extracted from `ResolverPool` (the language-only /
  x-default / single-store rules) so head tags and sitemap stay consistent. `ResolverPool` delegates
  to it (optional null-default ctor arg — existing tests untouched).
- `UrlRewriteFetcher::fetchAllForType` (one bulk query per entity type) + `LinkBuilder::buildFromPaths`.
- `Model/Hreflang/SitemapGenerator` — one `<url>` block per entity per store view, each with the full
  `<xhtml:link>` set; home pages from store base URLs; ENT_XML1 escaping.
- `Controller/Hreflangsitemap/Index` (404 when disabled or <2 stores; `Cache-Control` 24h +
  `X-Magento-Tags: MAGEOS_SEO_HREFLANG_SITEMAP`) served via `Model/Router/HreflangSitemapRouter` (clean
  `/hreflang-sitemap.xml`, mirrors the llms router).
- `Config::isHreflangSitemapEnabled()` + `hreflang/sitemap_enabled` config/admin field.
- `Observer/InvalidateHreflangSitemapCache` on product/category/cms/store save (etc/events.xml).
- Tests: `AlternateBuilderTest`, `SitemapGeneratorTest`, `UrlRewriteFetcher::fetchAllForType` cases,
  DI smoke test.

## Gate status (local, latest run — Phase 0 + 1 + 2 complete)

| Gate | Result |
|------|--------|
| php -l | ✅ |
| PHPUnit unit | ✅ 333 tests / 541 assertions |
| phpcs | ✅ 0 |
| php-cs-fixer | ✅ 0 |
| PHPStan | ✅ 0 |
| XML well-formed | ✅ |
| Infection MSI ≥ 54 (enforced gate; 75 target) | CI (tests written mutation-first) |
| Integration + di:compile | CI |

## Phase 3 — AEO content + identity ✅

### 3a — Article / Event / Speakable ✅
- `Api/ArticleDataProviderInterface` + `Api/EventDataProviderInterface` (bridge-fed pools, empty by
  default) → `Model/StructuredData/Provider/ArticleSchemaProvider` (BlogPosting; author+publisher
  reference the shared Organisation `@id`) and `EventSchemaProvider` (one Event node per event,
  organizer = Organisation `@id`). Both reuse `HandleMatcher` to match sub-provider handles and the
  Phase-0c `OrganisationId` service.
- `Model/StructuredData/Provider/SpeakableProvider` — WebPage + SpeakableSpecification from config;
  disabled by default.
- New `aeo` config group (`speakable_enabled`, `speakable_css_selectors`) + `Config` getters; all
  three providers wired into the structured-data Compositor (Compositor DI smoke test covers them).
- Tests: `ArticleSchemaProviderTest`, `EventSchemaProviderTest`, `SpeakableProviderTest`.

### 3b — LocalBusiness expansion ✅ (opening-hours deferred)
- 10 nullable columns on `mageos_seo_organisation` (street/locality/region/postcode/country,
  telephone, email, latitude, longitude, price_range) + db_schema_whitelist; `OrganisationInterface`
  constants + `getAddress`/`getLatitude`/`getLongitude`/`getTelephone`/`getEmail`/`getPriceRange`
  getters + `setLocalPresence(array)`; model impl.
- **No separate LocalBusinessProvider** — the structured-data Compositor collects-all (no
  precedence), so a second org provider would duplicate the node. Instead `OrganisationProvider`
  emits `address` (PostalAddress), `geo` (GeoCoordinates), `telephone`, `email`, `priceRange` when
  populated; `@type` comes from the existing `org_type` (set it to Store/LocalBusiness subtype).
- Admin "Local Presence" fieldset in the Organisation form + Save controller mapping + DataProvider
  hydration.
- Tests: `OrganisationProviderTest` extended (populated/empty/geo-needs-both cases).
- ⏳ DEFERRED: `openingHoursSpecification` (needs a dynamic-rows admin UI) — own follow-up.

### 3c — FAQ subsystem 🟡 (engine done; rendering surface + admin + PB pending)

**3c-1 engine ✅** — `mageos_seo_faq` table (identifier, store_id, question, answer, sort_order,
is_active) + whitelist; `Model/Faq/Repository` (raw-connection read by identifier, store 0 + store
fallback, ordered); `Api/FaqSourceProviderInterface` + `Model/Faq/Source/TableFaqSource` +
`Model/Faq/SourcePool` (collect-all extension point); `Api/FaqCollectorInterface` +
`Model/Faq/Collector` (request-scoped, dedup, stores **identifiers** for block-cache-immune
re-resolution). di.xml: collector preference + source pool. Tests: Repository, SourcePool, Collector,
TableFaqSource + 2 DI smoke tests.

**3c-2 surface ✅** — `Block/AbstractFaqElement` (resolve via source pool → register identifier with
collector → expose to template) + `Block/Widget/FaqList` (`etc/widget.xml`, `ttl`-cacheable) +
`faq/list.phtml` (`<details>`/`<summary>`, no JS, theme-agnostic). Late `Block/FaqJsonLd` in
`before.body.end` re-resolves collected identifiers → one deduped FAQPage (gated on structured-data
config; CSP-nonce template). `FaqLlmsSectionProvider` injects the `global` FAQ group into
/llms.txt + /llms-full.txt (registered in LlmsTxtBuilder). Tests: `FaqJsonLdTest`, `FaqListTest`
(resolve/collect/memoise), `FaqLlmsSectionProviderTest`. Single-node emission (head-seeding can be
layered later). Verify-in-CI/Hyvä: `before.body.end` container renders.

**3c-3a CRUD data layer ✅** — `Api/Data/FaqInterface` (field accessors; entity_id via AbstractModel,
no typed setEntityId to stay compatible) + `Model/Faq` (AbstractModel) + `Model/ResourceModel/Faq`
+ `Collection` + `Api/FaqRepositoryInterface` + `Model/FaqRepository` (getById/save/delete/deleteById,
`instanceof AbstractModel` guard like OrganisationRepository). di.xml preference. Integration test
`Test/Integration/Model/FaqRepositoryTest` (CRUD round-trip + read-by-identifier, CI-run).

**3c-3b admin grid/form ✅** — ACL `MageOS_Seo::faq` + menu (Marketing → SEO → FAQ Manager).
Controllers `Adminhtml/Faq/{Index,NewAction,Edit,Save,Delete}` (admin frontName `mageos_seo`). Listing UI
component (`mageos_seo_faq_listing`) with grid collection virtualType + CollectionFactory mapping in
di.xml; `Ui/Component/Listing/Column/FaqActions` for edit/delete. Form UI component
(`mageos_seo_faq_form`) + `Ui/DataProvider/Faq/FormDataProvider` (data-persistor fallback) + Add/Back/
Save/Delete button blocks. Layouts `mageos_seo_faq_index|edit`. FAQs now fully manageable in admin.

**3c-3c native Page Builder content type ✅** — `view/adminhtml/pagebuilder/content_type/mageos_seo_faq.xml`
(menu_section `mageos_seo`) + edit form (`pagebuilder_mageos_seo_faq_form` extending
`pagebuilder_base_form`: identifier + optional heading) + form layout + master/preview Knockout
templates. **Server-side render** via `Plugin/PageBuilder/FaqRenderer` on
`Magento\Framework\Filter\Template::filter()` (the proven pattern from Reessolutions' category_grid):
finds the `data-content-type="mageos_seo_faq"` placeholder, decodes `data-identifier`/`data-heading`,
and renders the **existing `FaqList` widget** — so the collector, renderer and FAQPage schema parity
are all reused, with **no hard dependency on Magento_PageBuilder** (plugin no-ops when no placeholder
is present; content-type XML is inert without PB). Added `heading` support to `AbstractFaqElement` +
`list.phtml` + the widget. `module.xml` sequences Magento_Widget + Magento_PageBuilder.

**FAQ subsystem (3c) COMPLETE** — engine, widget, late FAQPage schema, llms section, admin CRUD, and
native PB content type. PB/JS/Knockout + the Filter\Template render plugin are CI/manual-validated.

## Gate status (local, latest run — Phases 0–3 complete)

| Gate | Result |
|------|--------|
| php -l | ✅ |
| PHPUnit unit | ✅ 374 tests / 620 assertions |
| phpcs | ✅ 0 |
| php-cs-fixer | ✅ 0 |
| PHPStan | ✅ 0 |
| XML well-formed | ✅ |
| Infection MSI ≥ 54 (enforced gate; 75 target) | CI (Phases 0–2 confirmed green in CI; tests written mutation-first) |
| Integration + di:compile | CI (Phases 0–2 confirmed green in CI) |

## Phase 4 — GEO ✅ (llms.jsonl + AI-bot robots + UCP/well-known done)

### 4a — /llms.jsonl ✅
- `Api/JsonlLineProviderInterface` (bridge pool for extra catalog lines) + `Model/LlmsJsonl/ProductLineBuilder`
  (compact JSON-LD Product node per product) + `Model/LlmsJsonl/JsonlBuilder` (enabled-product
  collection → NDJSON; appends provider lines). `Controller/Llmsjsonl/Index` (404 when disabled;
  `Content-Type: application/x-ndjson`; `X-Magento-Tags: MAGEOS_SEO_LLMS_JSONL`). Clean URL `/llms.jsonl` via
  the existing `LlmsTxtRouter` (added route). `Config::isLlmsJsonlEnabled()` + `llms_txt/jsonl_enabled`
  (off by default) config/admin field. **Dedicated** `Observer/InvalidateLlmsJsonlCache` on
  `catalog_product_save_after` (purges only `MAGEOS_SEO_LLMS_JSONL` — does NOT over-purge MAGEOS_SEO_LLMS/MAGEOS_SEO_LLMS_FULL).
  di.xml line-provider pool. Tests: `ProductLineBuilderTest` + DI smoke (JsonlBuilder is factory-based →
  integration/CI-validated, like the other repositories).

### 4b — AI-bot robots.txt directives ✅
- `Model/Config/Source/AiBots` — 14 known AI crawler user-agents (GPTBot, ChatGPT-User, OAI-SearchBot,
  ClaudeBot, anthropic-ai, PerplexityBot, Google-Extended, Applebot-Extended, Meta-ExternalAgent,
  Amazonbot, cohere-ai, Diffbot, CCBot, Bytespider); value = literal robots.txt User-agent token.
- `Plugin/Robots/AppendAiDirectivesPlugin` — `afterGetData` on `Magento\Robots\Model\Robots` appends a
  `# AI crawlers (managed by MageOS_Seo)` block with a per-bot `Allow: /` or `Disallow: /` rule (disallow
  set = config list). Directive-building logic lives in public `buildAiDirectives()` (unit-testable
  without the Robots subject); `afterGetData` is a thin wrapper that returns `$result` unchanged when the
  block is empty (disabled). **Disabled by default** → store robots.txt unchanged out of the box.
- Config: `ai_robots/enabled` (0) + `ai_robots/disallowed` (CCBot,Bytespider) → `Config::isAiRobotsEnabled()`,
  `Config::getAiDisallowedBots()` (CSV → trimmed string[]). `system.xml` `ai_robots` group (showInDefault).
- `composer.json` now requires `magento/module-robots` (we sequence it in `module.xml` and plug onto its
  `Robots` model); di.xml plugin `mageos_seo_ai_robots_directives` sortOrder 10.
- Tests: `AiBotsTest` (major agents present, value/label non-empty, unique), `AppendAiDirectivesPluginTest`
  (disabled → empty + no config read; disallow vs allow split; empty list → all-allow; `afterGetData`
  passthrough when disabled and append-with-spacing when enabled).

### 4c — /.well-known/ucp + ai-plugin.json + security.txt ✅
- **Registry extension point:** `Api/WellKnownEndpointInterface` (getName/isEnabled/getContentType/
  getCacheControl/render) + `Model/WellKnown/EndpointPool` (keyed by path segment). A bridge module
  serves a new `/.well-known/*` document by appending to the pool from its own di.xml — no edit here.
- `Model/Router/WellKnownRouter` (RouterList sortOrder 19) matches `/.well-known/{name}`, checks the
  pool, forwards to the single dispatcher `Controller/Wellknown/Index` (param `endpoint`). Unknown/
  disabled → 404; render `LocalizedException` → 500 (logged). Loop-guarded on module name like
  LlmsTxtRouter.
- Built-in endpoints (`Model/WellKnown/Endpoint/{Ucp,AiPlugin,SecurityTxt}Endpoint`) wrap builders:
  - `Model/Ucp/ProfileBuilder` — UCP manifest (spec 2026-04-08); capabilities from config toggles
    nested under `dev.ucp.shopping` + merged `CapabilityPool` providers; `signing_keys` only when a
    public JWK exists. **Security: refuses to emit a JWK containing `d` (private key) — throws.**
  - `Model/Ucp/AiPluginBuilder` — ai-plugin.json (name sanitised to model-safe slug, description
    truncated to 200, OpenAPI url → Magento REST schema, contact from trans_email support identity).
  - `Model/Ucp/SecurityTxtBuilder` — RFC 9116 (Contact normalised to mailto:, Expires, Policy,
    Preferred-Languages).
- `Api/UcpCapabilityProviderInterface` + `Model/Ucp/CapabilityPool` (collect-all, empty by default) —
  Phase 2 endpoint modules declare live capabilities via di.xml.
- `Console/Command/UcpKeygenCommand` (`mageos:seo:ucp:keygen --website=N`) — generates ECDSA P-256
  keypair, stores private PEM **encrypted** (EncryptorInterface + config writer, website/default scope),
  stores + prints the public JWK (x/y, never `d`).
- `Model/Ucp/UcpConfig` — all UCP config getters (website-scoped, read at store scope for hierarchy
  resolution); merchant id auto-derived (reverse domain) when blank. config.xml `mageos_seo_ucp`
  (all OFF by default), system.xml `mageos_seo_ucp` section (general/capabilities/signing/ai_plugin/
  security_txt). di.xml endpoint pool + CommandList registration. composer.json already requires
  magento/module-robots (4b).
- Tests: ProfileBuilder (minimal/capabilities/pool-merge/JWK-include/**leak-throws**/invalid-json),
  AiPlugin (slug/truncate/fallback), SecurityTxt (mailto/qualified-uri/omit-blank), UcpConfig
  (derive/configured/fallback/flags), CapabilityPool, EndpointPool, Endpoint wrappers, WellKnownRouter
  (non-match/unregistered/loop-guard/forward), Keygen (**no `d` in stored JWK**/encrypted/scope). The
  thin dispatcher controller is covered by CI integration + a DiWiringTest pool-wiring smoke (factory-
  based, no unit test — codebase convention). 420 unit tests / 806 assertions.

## Gate status (local, latest run — Phases 0–4 complete)

| Gate | Result |
|------|--------|
| php -l | ✅ |
| PHPUnit unit | ✅ 420 tests / 806 assertions |
| phpcs | ✅ 0 |
| php-cs-fixer | ✅ 0 |
| PHPStan | ✅ 0 |
| XML well-formed | ✅ |
| Infection MSI ≥ 54 (enforced gate; 75 target) | CI (tests mutation-first) |
| Integration + di:compile | ✅ CI-confirmed (Phases 0–4 all green) |
