# Reassessment against main — August 2026

Follow-up to the July 2026 bundling-assessment review, which recorded 31 issue
drafts (5 blockers, 25 majors, 1 naming item) on branch
`claude/seo-module-assessment-dc5x47`. This document re-checks every one of
those issues against current `main` (`14b87ea`, 50 commits after the
assessment branch point) by inspecting the code — not the changelog — and then
re-evaluates the module's readiness for Mage-OS core.

## Verdict in one paragraph

All 5 blockers and all 25 majors are genuinely fixed on main, including the
"related minor" sub-items inside the issue bodies, and the naming pass (issue
31) landed in full. Two items are *partially* complete: release hygiene (30 —
tags and a CHANGELOG exist, but no release has been cut containing the fix
pass and no release automation is wired) and test breadth (29 — unit coverage
grew from 66 to 100 files and now covers all 16 builders, but integration
coverage is still 4 files with zero admin-controller tests). The module has
moved from "promising but unshippable" to "credible release candidate". What
remains for core inclusion is not bug-fixing but productisation: i18n, a
headless/API surface, integration-test depth, release/distribution mechanics,
and a scope decision on the fast-moving GEO endpoints.

## Issue-by-issue status

| # | Issue | Status on main | Verified by |
|---|-------|----------------|-------------|
| 01 | Optional DI args → enricher/rating pools never applied | **Fixed** | Pool/resolver collaborators are required constructor args everywhere; `Test/Integration/DiWiringTest.php` now asserts pool *contents* (`itemCondition`, `returnPolicy`, `shippingDetails`, `native`) |
| 02 | Adapter `getTableName()` breaks table-prefix installs | **Fixed** | Zero adapter `getTableName()` calls remain; 8 call sites use `ResourceConnection::getTableName()` |
| 03 | Invalid `\!` JSON escape corrupts JSON-LD | **Fixed** | `Compositor` and `FaqJsonLd` encode with `JSON_HEX_TAG \| JSON_HEX_AMP \| JSON_UNESCAPED_UNICODE \| JSON_UNESCAPED_SLASHES`; `str_replace` gone; pretty-print gone |
| 04 | Fallback canonical block wrong sitewide | **Fixed** | `Block/Canonical.php` rewritten: home/CMS pages only, built from store base URL (never Host header), suppression via canonical asset content type |
| 05 | `INDEX,FOLLOW` defaults override core design robots | **Fixed** | `etc/config.xml` ships empty robots defaults with an explanatory comment; core `design/search_engine_robots` stays in charge |
| 06 | Non-existent / non-Product schema types | **Fixed** | `Apparel`/`Food` → `Product`; `Book` → `['Product','Book']`, `Software` → `['Product','SoftwareApplication']`, etc. via `getSchemaType(): string\|array` |
| 07 | Invalid schema.org properties per type | **Fixed** | Builders reworked alongside 06; all 16 builders now have per-builder unit tests asserting types and properties (commit `b86902d`) |
| 08 | Base-currency prices labelled with display currency | **Fixed** | `AbstractBuilder` routes offer price, AggregateOffer low/high through `CurrencyService::convertFromBase()`; GBP fallback removed |
| 09 | Fabricated `priceValidUntil`; availability gaps | **Fixed** | Active `special_to_date` preferred; synthetic window store-timezone-aware and disableable (0 months); hardcoded `itemCondition` removed; backorders emit `BackOrder` |
| 10 | GTIN unvalidated as gtin13 | **Fixed** | `Model/Product/GtinValidator` (length + GS1 check digit) routes to `gtin8/12/13/14` or omits; override path also validated; barcode-attribute path in Electronics/Tool/Stationery fixed in follow-up `f8f6d55` |
| 11 | `removeDefaultCanonical()` removes arbitrary assets | **Fixed** | Removal by asset content type `canonical`, not URL-key regex |
| 12 | Duplicate hreflang per shared locale | **Fixed** | `StoreLocaleMap` scopes to current website (config `same_website_only`) and dedupes by locale with deterministic lowest-store-ID winner |
| 13 | Non-deterministic hreflang URL selection | **Fixed** | `UrlRewriteFetcher` filters `metadata IS NULL` and orders `url_rewrite_id ASC` on both paths |
| 14 | Hreflang sitemap unbounded, no 50k chunking | **Fixed** | Feeds pre-generated to files (queue consumer + nightly cron); `SitemapGenerator` chunks below 50k with a sitemap index |
| 15 | `/llms.jsonl` unbounded build + N+1 | **Fixed** | Paginated collection (`setPageSize`), `addUrlRewrite()`, batched `AreProductsSalableInterface` |
| 16 | `/llms-full.txt` COUNT N+1 + cross-website leak | **Fixed** | `setStoreId()` + root-path filter + active-subtree walk; `loadProductCount()` bulk load |
| 17 | Query-string cache-busting regeneration DoS | **Fixed** | Web requests never build feeds — missing file queues a rebuild and answers 503 Retry-After; `CanonicalPathRedirect` 301s query-string and standard-router URL variants to the canonical path |
| 18 | robots.txt `Allow: /` groups exempt AI bots | **Fixed** | `Disallow: /` groups emitted only for disallowed bots; allowed bots fall through to `User-agent: *` (further hardened for null robots data in `14b87ea`) |
| 19 | SVG upload stored-XSS | **Fixed** | SVG dropped from whitelist; `addValidateCallback` + `getimagesize` content sniffing on raster types |
| 20 | Admin state changes via GET | **Fixed** | All admin controllers declare `HttpPostActionInterface`/`HttpGetActionInterface`; grid delete action uses `'post' => true` with confirm; server-side FAQ validation added |
| 21 | Store scoping reads store 0 in adminhtml | **Fixed** | All four classes read `(int) $request->getParam('store', 0)`; category form shows ancestor-inherited values |
| 22 | Save plugins: global area, no try/catch, silent JSON loss | **Fixed** | Registered in `etc/adminhtml/di.xml`; persistence wrapped in try/catch with logging; invalid override-JSON surfaces an admin error instead of wiping data |
| 23 | FAQ widget `ttl` defeats collector | **Fixed** | `ttl` removed from `etc/widget.xml` with an explanatory comment |
| 24 | No `IdentityInterface` / FPC invalidation | **Fixed** | `Faq`, `Organisation` models + FAQ/JsonLd blocks implement `IdentityInterface`; FAQ/Organisation saves also invalidate the llms feeds (`FeedInvalidator`), closing the related-events gap |
| 25 | BreadcrumbList Hyvä-only; `makers_*` handles | **Fixed** | Luma fallback via `CatalogHelper::getBreadcrumbPath()`; exclusions DI-configurable, `makers_*` removed |
| 26 | composer.json missing ~8 hard requires | **Fixed** | All runtime modules declared (backend, ui, widget, media-storage, page-cache, cache-invalidate, directory, config, message-queue); `license: OSL-3.0` added; CatalogInventory replaced by MSI API packages |
| 27 | Deprecated `Registry` in five providers | **Fixed** | Isolated behind a single `Model/Catalog/CurrentEntity` shim |
| 28 | PageTitle + CanonicalUrlManager dead code | **Fixed** | Compositor wired via `Observer/ApplyPageTitle` (frontend events); providers act only on explicit titles; `CanonicalUrlManager` documented as the bridge-module API |
| 29 | Test coverage gaps; MSI gate 54 vs documented 75 | **Partially fixed** | 100 unit test files (was 66); all 16 builders, canonical block, routers, breadcrumbs, feeds covered; gate honest at `minMsi: 58` with a documented raise-as-you-go policy. Remaining: 4 integration files, zero admin-controller tests — see below |
| 30 | Release hygiene: no tags/CHANGELOG/version discipline | **Partially fixed** | Tags `v1.0.0`/`v1.1.0` exist, `CHANGELOG.md` added, `version` field removed. Remaining: the entire fix pass sits in `[Unreleased]` — no release has been cut from it; no release automation despite the `x-release-please-version` markers; `repositories` mirror entry still ships in composer.json |
| 31 | `rs_seo`/`RS_*`/hyphenated-table naming | **Fixed** | Zero `rs_seo`/`rs-seo`/`RS_*`/`makers_*` references outside docs/tests; tables `mageos_seo_*`; cache tags `MAGEOS_SEO_*`; Organisation spelling settled |

Main also went beyond the issue list: queue-based feed pre-generation with a
nightly cron safety net and configurable storage dir, MSI service-contract
availability, `ResetAfterRequestInterface` across request-scoped state (worker
mode / FrankenPHP), a compat layer giving a support range of 2.4.6-p15 through
2.4.9 / PHP 8.1–8.5, CMS resolution via `GetPageByIdentifierInterface`,
PHPStan level 8, and a CI matrix via the graycore supported-version action.

## What's still needed for Mage-OS core

Nothing on the list below is a defect; these are the additions and decisions
that separate "good standalone module" from "belongs in the distribution".

### 1. Cut a release and wire release automation (issue 30's remainder)

The naming break was done pre-release precisely so it would never ship —
but that only pays off if a release now ships. Cut **v2.0.0** from current
main (the rename is breaking against v1.1.0), move `[Unreleased]` under it,
and add the release-please workflow the CI comments already anticipate.
Drop the `repositories` entry from the published composer.json. Until a
tagged release exists, no distribution discussion can start.

### 2. Ship an i18n dictionary

There is no `i18n/` directory. Every admin label, system.xml comment,
validation message and frontend string is untranslatable — a hard
convention for anything in the distribution. Generate `i18n/en_US.csv`
(`bin/magento i18n:collect-phrases`) and keep it in CI.

### 3. Add a headless/API surface

The module is entirely layout-block based. Hyvä and Luma both work
(templates are plain PHP `<details>`/meta markup — verified no jQuery/
RequireJS dependencies), but PWA Studio, Vue Storefront and other headless
consumers get nothing. Two steps, in order of cost:

- **Cheap:** `etc/webapi.xml` for the already-existing `Api/`
  repositories (FAQ, Organisation) — the service contracts are there,
  they're just not exposed.
- **Structural:** a companion `mage-os/module-seo-graph-ql` exposing
  resolved JSON-LD, meta/OG tags, robots and hreflang per route, mirroring
  how core splits GraphQL into sibling modules. Without this, calling it
  the distribution's SEO answer overstates it for the headless segment.

### 4. Deepen integration tests (issue 29's remainder)

Unit coverage is now respectable; integration coverage is 4 files. The
mutation-test exclude list in `infection.json5` is an honest, ready-made
worklist — those classes are excluded precisely because only integration
tests can cover them. Priorities:

- Admin controller tests (`AbstractBackendController`) for the 8 CRUD
  actions — the standard core pattern, currently zero.
- A feed-pipeline test: invalidate → consumer run → file exists →
  controller serves 200 (and 503 before).
- The SQL-building repositories (`Category/*Repository`) against a real
  adapter, then raise `minMsi` as the excludes shrink.

### 5. Decide the scope of the GEO/agentic surface

The SEO core (structured data, canonicals, robots meta, hreflang, OG/meta,
FAQ) is stable, standards-based, and clearly core-worthy. The GEO layer —
`llms.txt`, `llms.jsonl`, `ai-plugin.json`, the UCP `.well-known` profile —
tracks specs that are young or already fading (OpenAI deprecated the
plugin manifest ecosystem that `ai-plugin.json` serves; `llms.txt`
adoption is still contested; UCP is a moving draft). Bundling those into
core creates a long-term deprecation contract for endpoints that may be
obsolete before the next LTS. Options, in order of preference:

1. Split GEO/`.well-known` into a satellite module
   (`mage-os/module-seo-agentic`) that the distribution can adopt or drop
   independently; or
2. keep them in-module but all **off by default** (`llms.txt` and
   `llms-full.txt` are currently on by default) and documented as
   experimental.

Either way, `ai-plugin.json` specifically should be re-justified or
removed before a core release.

### 6. Distribution mechanics

- Transfer the canonical repo to the `mage-os` org; publish to
  `mirror.mage-os.org` and Packagist under `mage-os/module-seo`.
- Recommend entering the distribution as an **official optional module**
  for one release cycle (installable, documented, supported) before
  promotion into the default metapackage — the recent community fix
  (`14b87ea`) shows real-world usage is only just beginning.
- Add the module to the Mage-OS docs site from the existing `docs/` tree,
  and to the Hyvä compatibility list (it is compatible; say so).

### 7. Small polish items observed during re-verification

- `robots.txt` never advertises `/hreflang-sitemap.xml` via a `Sitemap:`
  line; the AI-directives plugin is the natural place to append one when
  the sitemap is enabled.
- README documents the queue-based feeds but not the operational
  requirement in one line: the `mageosSeoFeedRegenerate` consumer runs via
  `consumers_runner` cron by default; deployments that manage consumers
  manually (supervisor) must add it.
- `etc/config.xml` default for `llms`/`llms_full` (`enabled=1`) should be
  revisited together with item 5.

## Bottom line

The July assessment concluded the module was not bundleable without a
substantial hardening pass. That pass happened, comprehensively — every
blocker and major verified fixed in code, plus meaningful architecture
upgrades (pre-generated feeds, MSI contracts, worker-mode safety) beyond the
review's asks. The remaining work is a productisation checklist (release,
i18n, API surface, integration tests, GEO scope decision, org transfer), not
a quality objection. With items 1–4 done and a decision recorded on item 5,
this is a realistic and genuinely valuable core addition: no other Magento
distribution ships first-party structured data, hreflang, and answer-engine
support, and the provider-pool architecture is exactly the extension surface
a distribution wants to standardise on.
