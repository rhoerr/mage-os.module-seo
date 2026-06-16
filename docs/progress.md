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

## Gate status (local, latest run — Phase 0 + Phase 1)

| Gate | Result |
|------|--------|
| php -l | ✅ |
| PHPUnit unit | ✅ 282 tests / 466 assertions |
| phpcs | ✅ 0 |
| php-cs-fixer | ✅ 0 |
| PHPStan | ✅ 0 |
| XML well-formed | ✅ |
| Infection MSI ≥ 75 | CI (tests written mutation-first) |
| Integration + di:compile | CI |

## Phases 2–4

⏳ Not started. See [`planned-features/_roadmap.md`](planned-features/_roadmap.md).
