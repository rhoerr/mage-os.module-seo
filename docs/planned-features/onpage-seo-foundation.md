# Planned Feature: On-Page SEO Foundation

**Status:** Planned (Phase 0).
**Complexity:** Low.
**Priority driver:** Three net-new, low-risk items that close classic on-page SEO gaps and provide
the `@id` foundation every AEO feature depends on. Build alongside the
[robots meta pool](robots-meta-pool.md).

## 1. Organisation `@id` foundation (blocks Phase 3)

AEO relies on linking all schema nodes to one Organisation entity by `@id`, so an answer engine that
resolves the Organisation once trusts every node that references it. Today `OrganisationProvider`
constructs `{baseUrl}/#organization` inline; other providers can't reuse it consistently.

- Add `Config::getOrganisationId(int $storeId): string` returning `{baseUrl}/#organisation` (single
  canonical form). Use the store base URL via `StoreManagerInterface` as existing providers do.
- Refactor `OrganisationProvider` to use it. Every future provider (WebSite, LocalBusiness, Event,
  BlogPosting, FAQ) references the **same** id — see
  [multistore-aeo.md](multistore-aeo.md) and [faq.md](faq.md).

> Note the existing node uses `#organization` (US spelling) while the module/table use
> `organisation`. Pick one canonical `@id` string and migrate `OrganisationProvider` to it in the
> same change so all nodes agree. Recommend `#organization` (matches schema.org examples and the
> current emitted value) to avoid changing live output.

## 2. Open Graph / Twitter completeness

`Model/MetaTag/Provider/*` emit a minimal OG set; several high-value tags are missing.

Add to the meta providers (product, category, CMS) and `view/frontend/templates/seo/meta-tags.phtml`:

| Tag | Source |
|-----|--------|
| `og:site_name` | Organisation name (`OrganisationRepository`) → store name fallback |
| `og:locale` | store locale, BCP-47 (`en_GB` → `en_GB` per OG spec uses underscore) |
| `og:image:width` / `og:image:height` | image dimensions where known (product gallery / logo) |
| `twitter:card` | `summary_large_image` |
| `twitter:title` / `twitter:description` / `twitter:image` | mirror the OG values |

The meta-tags template already supports both `property` and `name` attributes, so Twitter `name="…"`
tags need no template change beyond the providers returning them. Keep everything behind the existing
`isOgTagsEnabled()` switch (consider a separate `twitter_cards/enabled` toggle).

## 3. Pagination canonical / robots

Category listing pages with `?p=N` (and layered-nav filters) create duplicate content.

- **Canonical:** extend `CanonicalUrlManager` usage so paginated category URLs canonicalise to the
  unparameterised page-1 URL (or self-canonical — make it configurable).
- **Robots:** coordinated with [robots-meta-pool.md](robots-meta-pool.md) — the
  `CategoryRobotsProvider` applies the pagination policy (`noindex,follow` on `p>1`, or index with
  page-1 canonical). One configurable default; document the chosen behaviour.
- Layered-nav filter params: default to `noindex,follow` to avoid crawl-budget waste, configurable.

## New / changed files

| Action | File |
|--------|------|
| EDIT | `Model/Config.php` — `getOrganisationId()` (+ pagination/twitter getters) |
| EDIT | `Model/StructuredData/Provider/OrganisationProvider.php` — use `getOrganisationId()` |
| EDIT | `Model/MetaTag/Provider/{ProductMetaProvider,CategoryMetaProvider,CmsPageMetaProvider}.php` |
| EDIT | `view/frontend/templates/seo/meta-tags.phtml` (only if new attribute handling needed) |
| EDIT | `Model/Canonical/CanonicalUrlManager.php` (or a new pagination-aware caller) |
| EDIT | `etc/config.xml`, `etc/adminhtml/system.xml` — new toggles/defaults |

## Tests

- **Unit:** `getOrganisationId()` format; meta providers include the new tags when enabled and omit
  them when disabled/empty (negative cases for infection); pagination canonical computation.
- **Integration** (`@magentoAppArea frontend`): product/category/CMS emit OG+Twitter tags;
  `?p=2` category page emits the configured canonical + robots.

## Quality gates

Standard suite — see [_roadmap.md](_roadmap.md#quality-gates--definition-of-done-every-phase).
The `@id` migration changes emitted JSON-LD, so update any existing
`OrganisationProviderTest` expectations in the same change to keep unit + integration green.
