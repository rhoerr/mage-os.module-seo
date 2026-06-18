# SEO / AEO / GEO — Master Roadmap

This is the index for all planned work on `MageOS_Seo`. It unifies the per-feature specs in this
folder into one dependency-ordered roadmap and states the single architectural rule they all follow.

- **SEO** — classic search (canonical, hreflang, robots, meta, structured-data basics).
- **AEO** — answer engines / AI Overviews, Perplexity, Copilot (reviews, FAQ, rich product schema,
  LocalBusiness, Article/Event, entity `@id` linking, llms.txt).
- **GEO** — generative / agentic engines (llms.jsonl, `/.well-known/ucp`, ai-plugin, security.txt,
  AI-bot directives).

## The one rule: everything is a provider pool

Every cross-cutting concern is an **extensible provider pool**, never a hardcoded plugin or a single
`preference` binding, so a second module (blog, reviews vendor, agentic-commerce bridge) can plug in
via its own `di.xml` without editing `MageOS_Seo`. The pattern and shared conventions are specified
once in [architecture-provider-pools.md](architecture-provider-pools.md) and referenced by every
phase below.

The module already proves the pattern: `StructuredData\Compositor`, `MetaTag\Compositor`,
`PageTitle\Compositor`, `Product\SchemaBuilderPool`, `LlmsTxtBuilder.sectionProviders`. The work
below extends it to every remaining concern and retrofits the two that don't yet follow it
(robots meta; the reviews/merchant `preference` bindings).

## Phases

| Phase | Theme | Specs | Depends on |
|-------|-------|-------|------------|
| 0 | Foundation & quick wins | [architecture-provider-pools](architecture-provider-pools.md), [robots-meta-pool](robots-meta-pool.md), [onpage-seo-foundation](onpage-seo-foundation.md) | — |
| 1 | Product / Offer richness | [merchant-policies](merchant-policies.md), [aggregate-rating](aggregate-rating.md) | Phase 0 (`@id`) |
| 2 | Multistore SEO | [hreflang](hreflang.md) | independent |
| 3 | AEO content + identity | [multistore-aeo](multistore-aeo.md), [faq](faq.md) | Phase 0 (`@id`, kernel) |
| 4 | GEO / agentic | [llms-jsonl](llms-jsonl.md), [ucp-and-well-known](ucp-and-well-known.md) | Phase 0 robots pool (reused) |

### Phase 0 — Foundation & quick wins

Build the pool kernel (`HandleMatcher`) and close net-new SEO gaps. Unblocks all AEO `@id` work.

- Pool kernel + shared conventions — [architecture-provider-pools.md](architecture-provider-pools.md).
- Robots meta retrofit to a pool, **CMS robots** (closes the long-standing gap), enriched
  directives — [robots-meta-pool.md](robots-meta-pool.md).
- `@id` foundation (`Config::getOrganisationId()`), OG/Twitter completeness, pagination
  canonical/robots — [onpage-seo-foundation.md](onpage-seo-foundation.md).

### Phase 1 — Product / Offer richness

The two consumers of `AbstractBuilder::buildBase()`, built together to touch the builder once.

- Offer enrichment **pool** (shipping / returns / itemCondition / priceValidUntil) —
  [merchant-policies.md](merchant-policies.md).
- Reviews as a **priority pool** (native + bridge vendors), plus `AggregateOffer` for configurables —
  [aggregate-rating.md](aggregate-rating.md).

### Phase 2 — Multistore SEO

On-branch priority (`feature/multistore`). Already a resolver pool.

- hreflang `<head>` tags + `/hreflang-sitemap.xml` — [hreflang.md](hreflang.md).

### Phase 3 — AEO content + identity

All ride the structured-data pool + the Phase-0 `@id` foundation.

- WebSite/SearchAction + LocalBusiness expansion; Article/Event bridge pools; Speakable —
  [multistore-aeo.md](multistore-aeo.md).
- FAQ subsystem (data, source pool, renderer, collector, widget, native Page Builder type) —
  [faq.md](faq.md).

### Phase 4 — GEO / agentic

- AI-bot robots directives — delivered through the Phase-0 robots pool, not a separate mechanism.
- `/llms.jsonl` NDJSON catalog with a line-provider pool — [llms-jsonl.md](llms-jsonl.md).
- `/.well-known/*` endpoint **registry** + UCP capability pool + keygen CLI —
  [ucp-and-well-known.md](ucp-and-well-known.md).

## Dependency chain

```text
HandleMatcher kernel
  └─> robots pool + @id foundation (Phase 0)
        └─> Offer/rating pools (Phase 1)        [need @id]
  ┌─> hreflang (Phase 2)                        [independent]
        └─> AEO content incl. FAQ (Phase 3)     [need @id + kernel]
              └─> GEO (Phase 4)                  [robots pool reused; well-known registry independent]
```

## Quality gates — definition of done (every phase)

A phase is complete only when the full suite is green. These map to
[`.github/workflows/ci.yml`](../../.github/workflows/ci.yml):

| Gate | Where it runs | Requirement |
|------|---------------|-------------|
| phpcs | CI `check-extension` (graycoreio) + `composer test:phpcs` | 0 violations |
| php-cs-fixer | local `composer test:cs-fixer` | 0 diffs (`--allow-risky=yes`) |
| PHPStan | CI `static` job (PHP 8.3) + `composer test:phpstan` | 0 errors |
| Unit tests | CI `check-extension` + `composer test:unit` | all pass |
| Integration tests | CI `check-extension` (real Magento, version matrix) | all pass |
| Mutation tests | CI `infection` (on PRs) + `composer test:infection` | MSI ≥ 75 (`infection.json5`) |
| DI compile | CI `check-extension` | `setup:di:compile` zero errors |

Design implications of these gates (precise array shapes for PHPStan, negative-case unit tests for
infection, zero `Magento\PageBuilder\*` type hints in always-loaded PHP for di:compile, etc.) are
restated in each spec's own "Quality gates" section.
