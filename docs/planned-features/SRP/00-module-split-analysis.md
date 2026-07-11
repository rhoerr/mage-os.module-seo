# Splitting MageOS_Seo into Feature Modules — Analysis

**Status:** Proposal / decision aid — not yet actioned
**Audience:** MageOS maintainers weighing whether the single `mage-os/module-seo`
package should become a family of feature packages.
**Date:** 2026-06-18

> This is deliverable **(a)** of three. See also:
> - [01-dependency-graph-and-composer.md](01-dependency-graph-and-composer.md) — the concrete
>   module graph + per-package `composer.json` require blocks (deliverable **b**).
> - [02-config-decomposition-prototype.md](02-config-decomposition-prototype.md) — a worked
>   prototype splitting `Model/Config` for one feature, to size the real cost (deliverable **c**).

---

## TL;DR recommendation

**Do not do a flat "one module per feature" split.** It maximizes coordination cost for marginal
reuse benefit on the small features (Canonical, PageTitle, Speakable).

**Do a tiered split** along two axes — *install-decision* (would a merchant decline this?) and
*volatility* (how often does it change?):

- **Keep a bundled kernel** (`MageOS_Seo`) holding the contracts, the rendering glue, and the
  small/stable/universal features.
- **Break out the 3–4 features that are genuinely optional _and_ fast-moving:** StructuredData,
  Hreflang, Llms, WellKnown/GEO.
- **Ship a `mage-os/seo` meta-package** that requires kernel + all features so the default install
  is still "one require."

This captures ~80% of the composability and release-cadence wins at ~30% of the coordination cost,
and it matches the actual coupling graph: the heavily-shared `Config` / `Organisation` / Compositor
core stays in one place; only loosely-coupled, fast-moving surfaces leave.

The single load-bearing prerequisite — whatever granularity is chosen — is **decomposing
`Model/Config`**. That is on the critical path for any split. Prototype it first (deliverable c).

---

## Framing: this is NOT an SRP decision

The internal pitch is "split features into their own modules, citing SRP." That framing will lead to
over-splitting. SRP is a **class**-level principle ("a class has one reason to change"), and this
codebase already honors it well — every concern is already a focused class behind a provider pool.

Module boundaries are governed by the **component-cohesion principles** (Robert C. Martin), which
are a different and sometimes _opposing_ set of forces:

| Principle | Plain meaning | Implication for the split |
|---|---|---|
| **REP** — Release/Reuse Equivalence | The unit of reuse is the unit of release. | Only split out what someone would genuinely install *without the rest*. |
| **CCP** — Common Closure | Classes that change together belong in the same package. | Group by *what changes together* (schema.org churn vs. stable hreflang). |
| **CRP** — Common Reuse | Classes used together belong together; don't force users to depend on things they don't use. | A package should not drag in unrelated transitive deps. |

The tension: **CCP and CRP pull apart; REP and the cost of release coordination pull together.**
The right question per feature is therefore *not* "does this have a single responsibility?" (almost
everything does) but **"would a merchant install this alone, and does it version independently?"**

---

## Current state (what the audit found)

One module, `MageOS_Seo` — 236 PHP files, 4 DB tables, ~13 admin config groups across 3 sections.

The decisive architectural fact: **the module is already built for extension via bridge modules.**
Every cross-cutting concern is a DI-array provider pool, and the `di.xml` comments explicitly tell
third parties to self-register via their own `di.xml` and "never edit MageOS_Seo":

- `StructuredData\Compositor` (8 built-in providers + bridge slot)
- `MetaTag\Compositor`, `PageTitle\Compositor`
- `Product\SchemaBuilderPool` (16 builders)
- `RobotsMeta\Resolver`, `Hreflang\ResolverPool`
- `Product\OfferEnricher\Pool`, `Review\AggregateRatingResolver`
- `WellKnown\EndpointPool`, `LlmsTxtBuilder.sectionProviders`, `LlmsJsonl\JsonlBuilder.lineProviders`

So the real question is **not** "can this be extended?" (it can, today, without a split) — it is
**"should the _built-in_ feature set ship as separately installable composer packages?"**

---

## The natural seams

The directory layout already maps 1:1 to shippable features. Each candidate has its own config
group, optionally its own table, and its own provider pool registration.

| Candidate package | Owns (dirs) | Table(s) | system.xml |
|---|---|---|---|
| **Kernel** `MageOS_Seo` | `Api/*`, the 3 Compositors, `Block/JsonLd`, `Block/MetaTags`, `view/.../default.xml`, `Model/Pool/HandleMatcher`, base config infra | — | sections shell |
| `…SeoIdentity` | `Model/Organisation*`, `Controller/Adminhtml/Organisation`, `Ui/.../Organisation*` | `_organisation` | — |
| `…SeoStructuredData` | `Model/StructuredData/*`, `Model/Product/Builder/*` (16), `SchemaBuilderPool`, `Model/Review/*` | — | structured_data, aeo |
| `…SeoMerchantPolicies` | `Model/Product/OfferEnricher/*`, `Service/CurrencyService` | — | mageos_seo_merchant |
| `…SeoMetaTags` | `Model/MetaTag/*`, `Model/PageTitle/*` | — | og_tags |
| `…SeoCanonical` | `Model/Canonical/*`, `Block/Canonical` | — | — |
| `…SeoRobots` | `Model/RobotsMeta/*`, AI-directives plugin | — | robots_meta, ai_robots |
| `…SeoHreflang` | `Model/Hreflang/*`, sitemap controller/router/observer | — | hreflang |
| `…SeoFaq` | `Model/Faq/*`, widget, PageBuilder plugin, admin CRUD | `_faq` | — |
| `…SeoLlms` | `Model/LlmsTxt/*`, `Model/LlmsJsonl/*`, controllers, observers | — | llms_txt |
| `…SeoWellKnown` (GEO) | `Model/WellKnown/*`, `Model/Ucp/*`, keygen CLI, well-known controller | — | mageos_seo_ucp |
| `…SeoCatalogOverrides` | `Model/Category/*`, catalog save plugins, product/category form modifiers | `_category_config`, `_product_override` | — |

---

## The coupling reality (this is what decides granularity)

Three things are genuinely shared and would have to live in the kernel:

1. **`Model/Config` — 24 consumers across every feature.** One class, 26 getters, spanning every
   feature's settings. This is the single biggest refactor cost of any split. A clean split means
   breaking it into per-module config classes, each owning its own getters + system.xml group. The
   work is mechanical but touches everything; see deliverable (c) for the worked cost.

2. **The frontend render kernel.** `Block/JsonLd` renders *all* structured-data providers through
   one Compositor; `Block/MetaTags` likewise; `default.xml` wires them into `head.additional`. The
   Compositors + these Blocks + the layout must live in the base. Feature modules contribute
   providers *to* the pool — they do not own the render entry point. This is fine (it is the pool
   pattern) but it makes the kernel a hard dependency for anything that renders into `<head>`.

3. **`Organisation` (merchant identity)** is consumed by StructuredData, Llms, and MetaTags. It is a
   low-level shared concern, not a peer feature → it belongs below the feature tier (its own small
   `…SeoIdentity` package, or folded into the kernel).

One behavioral coupling actually gets *cleaner* when split: the **FPC invalidation observers** fan
out across features today (`catalog_category_save_after` invalidates both llms and hreflang caches
from a shared `events.xml`). Split modules each register their own observer on the same event — the
cross-feature `events.xml` disappears.

Two feature pairs are **not independent** and must not be split apart:
- `MerchantPolicies` (OfferEnrichers) only has value *through* StructuredData's
  `AbstractBuilder::buildBase()`. → keep inside or hard-depend on StructuredData.
- The Llms FAQ section provider couples Llms → Faq (optional, suggest-level dependency).

---

## Pros / cons — the honest view

### Pros of splitting
- **Real composability (REP/CRP).** A headless/PWA store wants JSON-LD + llms.jsonl but not OG tags
  or canonical. Today they install dead code. *Caveat: only a win for features a merchant would
  actually decline — which excludes the small/universal ones.*
- **Independent release cadence (CCP) — the strongest argument.** Schema.org and the GEO/agentic
  surface (UCP, well-known, llms.jsonl) move monthly; hreflang and canonical are stable for years.
  Separate packages let the volatile ones ship fast without forcing a kernel version bump.
- **Smaller blast radius** for tests/CI per package; clearer code ownership.
- **Honest dependency declarations.** Today the kernel `composer.json` carries *everyone's* deps
  (`magento/module-cms`, `module-robots`, PageBuilder/Widget…). Splitting lets each package require
  only what it uses.
- **Marketing.** "Pick your SEO surface" is a compelling MageOS story.

### Cons of splitting
- **The version matrix — the dominant long-term cost.** N packages → combinatorial compatibility
  testing, plus a meta-package you must maintain to preserve the "just give me everything" path.
- **Shared-kernel churn.** Any change to a Compositor signature, an `Api/` interface, or
  `HandleMatcher` ripples to every dependent package as a coordinated multi-repo release. Today it
  is one commit.
- **Config split is non-trivial and user-facing.** The admin config tree fragments across packages;
  `Config`'s 24 consumers get rewired. Cut the kernel/feature config boundary wrong and you re-cut
  it later — painful once published. (Deliverable c sizes this.)
- **Cross-feature features get awkward.** MerchantPolicies↔StructuredData and Llms↔Faq dependencies
  partly defeat the "independent modules" goal.
- **Over-splitting risk (REP violation in the other direction).** Canonical, PageTitle, Speakable
  are tiny; a standalone package for each is ceremony with no reuse payoff.

---

## Recommended target architecture (tiered)

```
Tier 0  KERNEL — MageOS_Seo
        Api/ contracts · Compositors · Blocks + default.xml · HandleMatcher
        · base Config infra · (Identity: Organisation) · Canonical · PageTitle
        · MetaTags · Robots
        → small, stable, ~every store wants it. Bundling keeps the matrix sane.

Tier 1  OPTIONAL + VOLATILE feature packages (depend on kernel)
        MageOS_SeoStructuredData   (+ MerchantPolicies + Reviews inside it)
        MageOS_SeoHreflang         (multistore-only)
        MageOS_SeoLlms
        MageOS_SeoWellKnown        (GEO / UCP — fastest moving)
        MageOS_SeoFaq              (content authoring; own table + PageBuilder dep)
        MageOS_SeoCatalogOverrides (admin per-product/category SEO config)

Tier 2  META-PACKAGE — mage-os/seo
        requires kernel + all Tier-1 packages → default "one require" install
```

Rationale for what stays vs. leaves:
- **Stays in kernel:** Canonical, PageTitle, MetaTags, Robots, Identity — small, stable, ~universal.
  Splitting them is pure ceremony.
- **Leaves:** StructuredData (volatile, large, the MerchantPolicies/Reviews coupling lives inside
  it cleanly), Hreflang (only multistore stores want it; heavy `url-rewrite` dep), Llms + WellKnown
  (bleeding-edge, fastest cadence — exactly what CCP says to isolate), Faq + CatalogOverrides
  (carry their own tables / PageBuilder / admin surface that not every store wants).

See [01-dependency-graph-and-composer.md](01-dependency-graph-and-composer.md) for the edge list and
the actual `composer.json` require blocks.

---

## Sequencing if MageOS proceeds

1. **De-risk `Config` first** (deliverable c) — split one feature's config in-place inside the
   current single module. Validates the base-class/boundary design with zero packaging risk.
2. **Extract the kernel** — move contracts, Compositors, Blocks, `HandleMatcher`, Identity into the
   base package; everything else still ships from it temporarily.
3. **Carve Tier-1 packages one at a time**, lowest-coupling first: WellKnown → Llms → Hreflang →
   Faq → CatalogOverrides → StructuredData (last, it's the biggest and most depended-on).
4. **Introduce the meta-package** and flip the default install to it.
5. Each carve-out is a *mechanical move* (files + their di.xml/system.xml fragments + tests) once
   the Config boundary exists — the architecture's pool pattern means no provider re-registration
   logic changes, only *which package's di.xml* declares the pool item.
