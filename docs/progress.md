# Implementation Progress — SEO / AEO / GEO Roadmap

Tracks delivery against [`planned-features/_roadmap.md`](planned-features/_roadmap.md). Gate suite per
[`.github/workflows/ci.yml`](../.github/workflows/ci.yml): phpcs, php-cs-fixer, PHPStan, PHPUnit unit,
integration (CI), infection MSI ≥ 75 (CI), `setup:di:compile` (CI).

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
  `{orgUrl}/#organization`). `OrganizationProvider` migrated to it via an optional null-defaulted
  ctor arg — output identical, existing `OrganizationProviderTest` untouched and green. Ready for the
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
  `X-Magento-Tags: RS_HREFLANG_SITEMAP`) served via `Model/Router/HreflangSitemapRouter` (clean
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
| Infection MSI ≥ 75 | CI (tests written mutation-first) |
| Integration + di:compile | CI |

## Phase 3 — AEO content + identity 🟡 (3a done; LocalBusiness + FAQ pending)

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
- 10 nullable columns on `mage-os_seo_organisation` (street/locality/region/postcode/country,
  telephone, email, latitude, longitude, price_range) + db_schema_whitelist; `OrganisationInterface`
  constants + `getAddress`/`getLatitude`/`getLongitude`/`getTelephone`/`getEmail`/`getPriceRange`
  getters + `setLocalPresence(array)`; model impl.
- **No separate LocalBusinessProvider** — the structured-data Compositor collects-all (no
  precedence), so a second org provider would duplicate the node. Instead `OrganizationProvider`
  emits `address` (PostalAddress), `geo` (GeoCoordinates), `telephone`, `email`, `priceRange` when
  populated; `@type` comes from the existing `org_type` (set it to Store/LocalBusiness subtype).
- Admin "Local Presence" fieldset in the Organisation form + Save controller mapping + DataProvider
  hydration.
- Tests: `OrganizationProviderTest` extended (populated/empty/geo-needs-both cases).
- ⏳ DEFERRED: `openingHoursSpecification` (needs a dynamic-rows admin UI) — own follow-up.

### 3c — FAQ subsystem ⏳
- Data + source pool + collector + renderer + schema (head/late) + widget + native PB content type +
  admin UI. See `planned-features/faq.md`. Not started.

## Gate status (local, latest run — Phase 0 + 1 + 2 + 3a + 3b)

| Gate | Result |
|------|--------|
| php -l | ✅ |
| PHPUnit unit | ✅ 350 tests / 582 assertions |
| phpcs | ✅ 0 |
| php-cs-fixer | ✅ 0 |
| PHPStan | ✅ 0 |
| XML well-formed | ✅ |
| Infection MSI ≥ 75 | CI (Phases 0–2 confirmed green in CI; tests written mutation-first) |
| Integration + di:compile | CI (Phases 0–2 confirmed green in CI) |

## Phase 4

⏳ Not started. See [`planned-features/_roadmap.md`](planned-features/_roadmap.md).
