# SEO / AEO / GEO module rework — Phases 0–4

Reworks `MageOS_Seo` from a basic JSON-LD/meta module into a comprehensive **SEO + AEO + GEO**
module on the `feature/multistore` branch. Every cross-cutting concern is built as a **DI-array
provider pool** (interface + pool + thin applier), so bridge modules extend any surface via their own
`di.xml` without editing this module.

## Phase 0 — Foundation & quick wins
- Shared `HandleMatcher` pool kernel; StructuredData/MetaTag/PageTitle compositors delegate to it.
- Robots-meta provider pool with a single observer applier (replaces per-controller plugins);
  product/category/**CMS** providers (closes the CMS robots gap); enriched directives
  (`max-snippet`, `max-image-preview`, `noarchive`, `noai`, `noimageai`).
- Pagination robots provider for `?p=N` pages (opt-in).
- `@id` foundation (`{orgUrl}/#organization`) and site-level OG/Twitter card meta.

## Phase 1 — Product / Offer richness
- AggregateRating priority pool (native `review_entity_summary` fallback).
- Merchant policies via Offer-enricher pool: `itemCondition`, `hasMerchantReturnPolicy`,
  `OfferShippingDetails` (new `mageos_seo_merchant` config section, opt-in).
- `AggregateOffer` (lowPrice/highPrice) for configurable products.

## Phase 2 — Multistore SEO
- `<head>` hreflang tags via resolver pool (auto language-only + x-default; product/category/CMS
  resolvers).
- `/hreflang-sitemap.xml` with shared alternate-builder, clean-URL router, and cache invalidation.

## Phase 3 — AEO content + identity
- FAQ subsystem: pluggable source pool + collector, FAQPage JSON-LD, widget, admin CRUD, native
  Page Builder content type (no hard PB dependency), and llms.txt FAQ injection. New
  `mageos_seo_faq` table.
- Organisation / LocalBusiness identity model + admin form.

## Phase 4 — GEO / agentic
- `/llms.jsonl` NDJSON product catalog + line-provider pool + dedicated cache invalidation.
- AI-bot `robots.txt` directives for 14 known crawlers (off by default).
- `/.well-known/` registry serving UCP manifest, ai-plugin.json, and security.txt (RFC 9116);
  capability extension pool; ECDSA P-256 keygen CLI (encrypted private key, public JWK only).

## Cache & extensibility
- All endpoints are FPC-safe with `X-Magento-Tags` and save-event invalidation observers.
- New extension points: `RobotsMetaProviderInterface`, `AggregateRatingProviderInterface`,
  `OfferEnricherInterface`, `HreflangResolverInterface`, `FaqSourceProviderInterface`,
  `JsonlLineProviderInterface`, `WellKnownEndpointInterface`, `UcpCapabilityProviderInterface`.

## Testing / gates (local, latest run)
| Gate | Result |
|------|--------|
| PHPUnit unit | ✅ 420 tests / 806 assertions |
| phpcs / php-cs-fixer / PHPStan | ✅ 0 |
| Infection MSI ≥ 75 | CI (tests written mutation-first) |
| Integration + `setup:di:compile` | ✅ CI-confirmed (Phases 0–4) |
