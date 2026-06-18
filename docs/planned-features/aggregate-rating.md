# Planned Feature: AggregateRating (Reviews) Pool

**Status:** Planned (Phase 1). Supersedes Feature 4 of [multistore-aeo.md](multistore-aeo.md), which
specced a single `preference` binding.
**Complexity:** Low–Medium.
**Priority driver:** Product schema with verified review scores is the single biggest product
rich-result gap and a strong AEO signal — answer engines treat average ratings as facts in product
comparisons. Reviews must be **extensible to any provider** (native Magento, Yotpo, Trustpilot,
Okendo, …), so this is a **priority pool**, not a hardcoded or single-`preference` implementation.

## Why a pool, not a `preference`

A `preference` lets exactly one implementation exist; swapping in Yotpo means *replacing* the native
one, and two review sources can't coexist or be prioritised. A priority pool lets multiple providers
register and the highest-priority one that actually has data win — the native provider stays as a
low-priority fallback and bridges layer on top without editing `MageOS_Seo`.

## Architecture

```text
Api/AggregateRatingProviderInterface {
    getRating(int $productId, int $storeId): ?array;   // ['ratingValue','reviewCount','bestRating','worstRating'] or null
    getPriority(): int;                                // higher wins
}

Model/Review/AggregateRatingResolver   // iterate providers desc by priority; first non-null wins
Model/Review/NativeAggregateRatingProvider   // low-priority fallback (Magento native reviews)
```

The resolver is a **first-wins** pool per
[architecture-provider-pools.md](architecture-provider-pools.md). It is consumed via the Offer
enrichment pool (see [merchant-policies.md](merchant-policies.md)) by an `AggregateRatingEnricher`,
so rating flows into the product schema through the same path as shipping/returns and the builder is
touched once in Phase 1.

### NativeAggregateRatingProvider

Reads Magento's pre-aggregated `review_entity_summary`:

```sql
SELECT rating_summary, reviews_count
FROM review_entity_summary
WHERE entity_pk_value = :productId AND store_id = :storeId AND entity_type = 1
```

`rating_summary` is a 0–100 percentage → `ratingValue = round(rating_summary / 20, 1)`. Return
`null` when `reviews_count = 0` (never emit an `AggregateRating` with zero reviews). Priority low
(e.g. 100) so any bridge overrides it.

### Output merged into the product schema

```json
"aggregateRating": {
  "@type": "AggregateRating",
  "ratingValue": "4.3",
  "reviewCount": "17",
  "bestRating": "5",
  "worstRating": "1"
}
```

### Bridge example (Yotpo) — no core change

```xml
<!-- YourVendor_YotpoSeo/etc/di.xml -->
<type name="MageOS\Seo\Model\Review\AggregateRatingResolver">
    <arguments>
        <argument name="providers" xsi:type="array">
            <item name="yotpo" xsi:type="object">YourVendor\YotpoSeo\Model\YotpoRatingProvider</item>
        </argument>
    </arguments>
</type>
```

With `getPriority()` above the native provider, Yotpo wins where it has data and falls through to
native otherwise.

## AggregateOffer for configurables

Separately (same phase), configurable products should emit an `AggregateOffer` with
`lowPrice`/`highPrice`/`offerCount` instead of a single `Offer`, computed from child prices. Add this
to the relevant builder(s) / a shared helper in `AbstractBuilder`. Keep it behind the existing
`has_variant_max` style config where sensible.

## New / changed files

| Action | File |
|--------|------|
| NEW | `Api/AggregateRatingProviderInterface.php` |
| NEW | `Model/Review/AggregateRatingResolver.php` |
| NEW | `Model/Review/NativeAggregateRatingProvider.php` |
| NEW | `Model/Product/OfferEnricher/AggregateRatingEnricher.php` (registered in the Offer pool) |
| EDIT | `etc/di.xml` — register resolver + native provider + enricher |
| EDIT | builder(s) / `AbstractBuilder` — `AggregateOffer` for configurables |

Note: rating reaches the schema via the Offer enricher pool, so `AbstractBuilder`'s constructor
changes are shared with [merchant-policies.md](merchant-policies.md) — do both in one Phase-1 change.

## Tests

- **Unit:** `NativeAggregateRatingProvider` (zero reviews → null; percentage → 5-star rounding;
  store scoping); `AggregateRatingResolver` (priority ordering, null fallthrough, empty pool → null);
  `AggregateRatingEnricher` (no rating → empty fragment); `AggregateOffer` computation
  (low/high/count, single child edge case).
- **Integration** (`@magentoAppArea frontend`): product with reviews → `aggregateRating` in JSON-LD;
  product without reviews → node absent; configurable → `AggregateOffer`.
- **Mutation (MSI ≥ 75):** priority/fallthrough and the zero-review boundary need explicit tests.

> Review summaries come from a Magento indexer; integration tests must seed
> `review_entity_summary` (or trigger the summary) so `reviews_count` is non-stale.

## Quality gates

Standard suite — see [_roadmap.md](_roadmap.md#quality-gates--definition-of-done-every-phase).
PHPStan: `getRating()` returns a precise `array{ratingValue:string,reviewCount:string,...}|null`
shape; resolver typed `@param AggregateRatingProviderInterface[] $providers`.
