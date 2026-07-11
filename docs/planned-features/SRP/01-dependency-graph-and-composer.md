# Module Dependency Graph + composer.json Require Blocks

> Deliverable **(b)**. Companion to [00-module-split-analysis.md](00-module-split-analysis.md) and
> [02-config-decomposition-prototype.md](02-config-decomposition-prototype.md).

This document gives the concrete package set, the dependency edges, the `<sequence>` ordering for
`module.xml`, and a ready-to-paste `composer.json require` block per package. Package names follow
the existing vendor convention `mage-os/module-seo-*` / `MageOS_Seo*`.

---

## 1. The graph

```
                          ┌─────────────────────────────┐
                          │  mage-os/module-seo (KERNEL) │
                          │  Api · Compositors · Blocks  │
                          │  default.xml · HandleMatcher │
                          │  Config base · Identity      │
                          │  Canonical · PageTitle       │
                          │  MetaTags · Robots           │
                          └──────────────┬──────────────┘
                                         │ (every feature requires the kernel)
        ┌───────────────┬───────────────┼───────────────┬───────────────┐
        ▼               ▼               ▼               ▼               ▼
 ┌─────────────┐ ┌────────────┐ ┌────────────┐ ┌────────────┐ ┌──────────────────┐
 │StructuredData│ │  Hreflang  │ │    Faq     │ │CatalogOver.│ │    WellKnown     │
 └──────┬──────┘ └────────────┘ └─────┬──────┘ └────────────┘ └──────────────────┘
        │                             │
        ▼ (suggest)                   │ (suggest: adds FAQ section to llms.txt)
 ┌──────────────────┐                 │
 │ MerchantPolicies │                 ▼
 └──────────────────┘          ┌────────────┐
                               │    Llms    │◀── suggests Faq
                               └────────────┘

 META: mage-os/seo  ──requires──▶ kernel + all of the above
```

**Edge list (hard `require`):**

| Package | Hard requires (within the SEO family) |
|---|---|
| `module-seo` (kernel) | — |
| `module-seo-structured-data` | kernel |
| `module-seo-merchant-policies` | kernel, **structured-data** (feeds `AbstractBuilder::buildBase()`) |
| `module-seo-hreflang` | kernel |
| `module-seo-faq` | kernel |
| `module-seo-llms` | kernel |
| `module-seo-well-known` | kernel |
| `module-seo-catalog-overrides` | kernel |
| `mage-os/seo` (meta) | all of the above |

**Soft edges (`suggest`, not `require`):**

- `module-seo-llms` *suggests* `module-seo-faq` — when present, `FaqLlmsSectionProvider` adds a FAQ
  section to `llms.txt`. The section provider is registered by **Faq's** di.xml into the Llms pool,
  so Llms never references Faq classes → no hard dependency. If Faq is absent, the pool item simply
  isn't registered.
- `module-seo-structured-data` *suggests* `module-seo-merchant-policies` and any review integration
  (Yotpo/Trustpilot) — these append to the OfferEnricher / AggregateRating pools.

> **Why MerchantPolicies hard-requires StructuredData but Llms only suggests Faq:** an
> OfferEnricher's output is *merged into* a product Offer built by `AbstractBuilder` — it is
> meaningless without that builder, so the dependency is structural. The FAQ→Llms relationship is
> additive: each contributes an independent pool item that degrades gracefully when the other is
> absent. This is the pool pattern's payoff — *additive* features stay decoupled; *enriching*
> features depend on what they enrich.

---

## 2. module.xml `<sequence>` per package

Magento load order must respect the graph. The kernel sequences after the Magento modules it
extends; features sequence after the kernel; enrichers after the feature they enrich.

```xml
<!-- kernel: MageOS_Seo/etc/module.xml -->
<module name="MageOS_Seo">
    <sequence>
        <module name="Magento_Catalog"/>
        <module name="Magento_Store"/>
        <module name="Magento_Cms"/>
        <module name="Magento_Robots"/>
    </sequence>
</module>

<!-- feature: MageOS_SeoStructuredData/etc/module.xml -->
<module name="MageOS_SeoStructuredData">
    <sequence><module name="MageOS_Seo"/></sequence>
</module>

<!-- enricher: MageOS_SeoMerchantPolicies/etc/module.xml -->
<module name="MageOS_SeoMerchantPolicies">
    <sequence>
        <module name="MageOS_Seo"/>
        <module name="MageOS_SeoStructuredData"/>
    </sequence>
</module>

<!-- additive cross-feature: MageOS_SeoFaq must load before Llms so its pool item registers -->
<module name="MageOS_SeoLlms">
    <sequence>
        <module name="MageOS_Seo"/>
        <module name="MageOS_SeoFaq"/>  <!-- soft: only matters if Faq is installed -->
    </sequence>
</module>
```

> Note: listing `MageOS_SeoFaq` in Llms's `<sequence>` is safe even when Faq is **not** installed —
> Magento's `<sequence>` only orders modules that are present; it is not a hard dependency. The hard
> dependency is expressed in `composer.json` (`require` vs `suggest`), the load order in
> `module.xml`. Keep the two consistent: `require` ⇒ also in `<sequence>`; `suggest` ⇒ in
> `<sequence>` for ordering but **not** in `require`.

---

## 3. composer.json require blocks

Magento-version constraints (`*`) and the dev block are inherited from today's
[../../../composer.json](../../../composer.json); only the per-package `require` differences are
shown. PHP and `magento/framework` are required by every package.

### Kernel — `mage-os/module-seo`
Carries only what the kernel itself touches. Note `module-cms` drops to the features that actually
need it (StructuredData CMS provider, Robots CMS provider live in kernel → cms stays; if Robots is
later extracted, move it).

```json
"require": {
    "php": "~8.1.0 || ~8.2.0 || ~8.3.0 || ~8.4.0 || ~8.5.0",
    "magento/framework": "*",
    "magento/module-catalog": "*",
    "magento/module-store": "*",
    "magento/module-cms": "*",
    "magento/module-robots": "*"
}
```

### `mage-os/module-seo-structured-data`
```json
"require": {
    "php": "~8.1.0 || ~8.2.0 || ~8.3.0 || ~8.4.0 || ~8.5.0",
    "magento/framework": "*",
    "magento/module-catalog": "*",
    "magento/module-store": "*",
    "magento/module-cms": "*",
    "magento/module-review": "*",
    "mage-os/module-seo": "^1.0"
},
"suggest": {
    "mage-os/module-seo-merchant-policies": "Adds shipping/return/condition data to product Offers"
}
```

### `mage-os/module-seo-merchant-policies`
```json
"require": {
    "php": "~8.1.0 || ~8.2.0 || ~8.3.0 || ~8.4.0 || ~8.5.0",
    "magento/framework": "*",
    "magento/module-catalog": "*",
    "magento/module-directory": "*",
    "mage-os/module-seo": "^1.0",
    "mage-os/module-seo-structured-data": "^1.0"
}
```
> `module-directory` is for `Service/CurrencyService` (currency conversion in shipping rates).

### `mage-os/module-seo-hreflang`
```json
"require": {
    "php": "~8.1.0 || ~8.2.0 || ~8.3.0 || ~8.4.0 || ~8.5.0",
    "magento/framework": "*",
    "magento/module-store": "*",
    "magento/module-url-rewrite": "*",
    "magento/module-catalog-url-rewrite": "*",
    "magento/module-cms-url-rewrite": "*",
    "mage-os/module-seo": "^1.0"
}
```
> The url-rewrite deps are *only* needed by Hreflang's `UrlRewriteFetcher` — a clear CRP win, they
> leave the kernel entirely.

### `mage-os/module-seo-faq`
```json
"require": {
    "php": "~8.1.0 || ~8.2.0 || ~8.3.0 || ~8.4.0 || ~8.5.0",
    "magento/framework": "*",
    "magento/module-widget": "*",
    "magento/module-page-builder": "*",
    "magento/module-ui": "*",
    "mage-os/module-seo": "^1.0"
}
```
> PageBuilder + Widget leave the kernel here — today they sit in the kernel `module.xml` sequence
> solely for FAQ.

### `mage-os/module-seo-llms`
```json
"require": {
    "php": "~8.1.0 || ~8.2.0 || ~8.3.0 || ~8.4.0 || ~8.5.0",
    "magento/framework": "*",
    "magento/module-catalog": "*",
    "magento/module-store": "*",
    "mage-os/module-seo": "^1.0"
},
"suggest": {
    "mage-os/module-seo-faq": "Adds an FAQ section to llms.txt when installed"
}
```

### `mage-os/module-seo-well-known`
```json
"require": {
    "php": "~8.1.0 || ~8.2.0 || ~8.3.0 || ~8.4.0 || ~8.5.0",
    "magento/framework": "*",
    "magento/module-store": "*",
    "mage-os/module-seo": "^1.0"
}
```
> Note: the AI-robots.txt directives plugin (`Plugin/Robots/AppendAiDirectivesPlugin`) is closely
> related to GEO but currently lives with Robots. Decide whether `ai_robots` config + plugin move
> here (cohesive with UCP/GEO) or stay in kernel/Robots. Recommendation: move to WellKnown — it is
> agentic-discovery, not classic robots-meta.

### `mage-os/module-seo-catalog-overrides`
```json
"require": {
    "php": "~8.1.0 || ~8.2.0 || ~8.3.0 || ~8.4.0 || ~8.5.0",
    "magento/framework": "*",
    "magento/module-catalog": "*",
    "magento/module-ui": "*",
    "mage-os/module-seo": "^1.0"
}
```

### Meta-package — `mage-os/seo`
```json
{
    "name": "mage-os/seo",
    "description": "Full MageOS SEO/AEO/GEO suite (metapackage).",
    "type": "metapackage",
    "version": "1.0.0",
    "require": {
        "mage-os/module-seo": "^1.0",
        "mage-os/module-seo-structured-data": "^1.0",
        "mage-os/module-seo-merchant-policies": "^1.0",
        "mage-os/module-seo-hreflang": "^1.0",
        "mage-os/module-seo-faq": "^1.0",
        "mage-os/module-seo-llms": "^1.0",
        "mage-os/module-seo-well-known": "^1.0",
        "mage-os/module-seo-catalog-overrides": "^1.0"
    }
}
```

---

## 4. Versioning policy (manage the matrix)

The cons section of deliverable (a) names the version matrix as the dominant long-term cost. Tame it
with policy, not hope:

- **Kernel uses caret ranges, features pin to kernel minor.** Features `require "mage-os/module-seo":
  "^1.0"`. The kernel's `Api/` interfaces and Compositor signatures are the contract — treat any
  breaking change to them as a **major** bump that forces a coordinated feature release. Keep that
  surface tiny (it already is: the `Api/` folder).
- **Release the whole family on one version line for 1.x.** Don't let packages drift to wildly
  different majors early; lockstep majors (all 1.x, then all 2.x) keeps the matrix near-diagonal.
  Allow independent *minor/patch* cadence — that is where the CCP win actually lives.
- **CI: one matrix job per feature × supported Magento line, plus one "meta-package install" smoke
  test** that installs `mage-os/seo` and asserts the suite boots and the integration `DiWiringTest`
  passes. That single smoke test catches most cross-package wiring regressions cheaply.
- **`replace` for migration safety.** The kernel package should declare
  `"replace": { }` entries as features are extracted **out** of it, so a store upgrading from the
  monolithic `mage-os/module-seo` 1.0 doesn't end up with duplicated classes. Example: when Hreflang
  is extracted, `module-seo-hreflang` is added to the meta-package and the monolith's next minor
  removes the Hreflang classes — the meta-package guarantees existing installs still get them.
