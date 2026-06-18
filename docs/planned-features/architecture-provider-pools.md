# Architecture: Provider Pools

**Status:** Foundation (Phase 0) — partially exists, to be formalised and extended.

This is the universal extension pattern for `MageOS_Seo`. Every cross-cutting concern (structured
data, meta tags, titles, robots, offers, reviews, hreflang, FAQ, llms sections, well-known
endpoints) is implemented as a **provider pool** so other modules extend behaviour by registering a
provider in their own `di.xml` — `MageOS_Seo` is never edited to add a new source of data.

See [_roadmap.md](_roadmap.md) for how this applies per phase.

## The pattern

Each concern has three parts:

```text
XProviderInterface {
    getHandles(): array;          // ['*'] = every page; else layout-handle codes to match
    getX(int $storeId, ...): ?T   // returns null / [] when it has no opinion for this page
}

XPool / XCompositor               // DI array of providers; matches handles; resolves precedence
                                  // then either collects-all or picks first-wins

Applier (block | plugin | controller)   // the ONLY class that calls Magento APIs
```

Existing reference implementations to copy:

| Concern | Interface | Pool | Applier |
|---------|-----------|------|---------|
| Structured data | `Api/StructuredDataProviderInterface` | `Model/StructuredData/Compositor` | `Block/JsonLd` |
| Meta tags | `Api/MetaTagProviderInterface` | `Model/MetaTag/Compositor` | `Block/MetaTags` |
| Page titles | `Api/PageTitleProviderInterface` | `Model/PageTitle/Compositor` | (head) |
| Product schema | `Api/ProductSchemaBuilderInterface` | `Model/Product/SchemaBuilderPool` | `ProductSchemaProvider` |
| llms.txt sections | `Model/LlmsTxt/SectionProviderInterface` | `LlmsTxtBuilder` | `Controller/Llms/Index` |

## Shared conventions

1. **Registration via di.xml array.** Pools take an injected `array $providers`. Built-ins are
   wired in `MageOS_Seo/etc/di.xml`; bridges add items via their own `etc/di.xml`. Same style as the
   `Compositor` `<argument name="providers" xsi:type="array">` blocks already in `etc/di.xml`.
2. **`getHandles()` semantics.** `['*']` = run on every page. Otherwise the provider lists layout
   handle codes (`catalog_product_view`, `catalog_category_view`, `cms_page_view`, …); the pool
   runs it only when one of those is in the active layout handles.
3. **`null` / `[]` = no opinion.** A provider that doesn't apply to the current page returns empty;
   the pool skips it. Never throw for "not my page".
4. **Precedence by `sortOrder`.** Built-in providers use 100; bridges use 200+ to override. Two
   resolution modes, chosen per concern:
   - **collect-all** — merge every provider's output (structured data, FAQ, offer fragments, llms
     sections).
   - **first-wins** — highest-precedence non-empty result is used (robots value, aggregate rating,
     canonical URL).
5. **Store scope is always passed in.** Providers receive `int $storeId` (or read the current store)
   so multistore output is correct — matches `Config`'s `int|string|null $storeId` getters.
6. **Cache-safe by construction.** Output depends only on URL-keyed resources, so blocks stay
   FPC-cacheable; no `cacheable="false"`. Endpoints set explicit `Cache-Control` + `X-Magento-Tags`
   and have an invalidation observer (pattern: `Controller/Llms/Index`,
   `Observer/InvalidateLlmsTxtCache`).

## The `HandleMatcher` kernel

`Model/StructuredData/Compositor::handlesMatch()` and `Model/MetaTag/Compositor::handlesMatch()`
contain identical logic. Extract it into one shared collaborator and have both (and all new pools)
use it:

```php
// Model/Pool/HandleMatcher.php
final class HandleMatcher
{
    /**
     * @param string[] $providerHandles
     * @param string[] $activeHandles
     */
    public function matches(array $providerHandles, array $activeHandles): bool
    {
        if (in_array('*', $providerHandles, true)) {
            return true;
        }
        return (bool) array_intersect($providerHandles, $activeHandles);
    }
}
```

Inject it into both existing Compositors (constructor) and every new pool. This removes duplication
and gives one place to unit-test the matching rule (helps the infection MSI gate — see below).

## Resolution helper (optional)

For **first-wins** pools (robots, rating) a tiny shared resolver shape keeps behaviour consistent:
iterate providers ordered by `sortOrder`, return the first non-empty `getX()`. Document it inline in
each pool rather than over-abstracting; the two modes are simple enough that a shared base class is
not warranted (and a needless base class hurts the di:compile/PHPStan surface).

## Quality gates for pool code

- **PHPStan**: type the injected `array` via docblock generics
  (`@param XProviderInterface[] $providers`) exactly as the current Compositors do; give every
  `getX()` a precise `@return` (array shapes `array{...}` where output is structured).
- **Infection (MSI ≥ 75)**: pools/resolvers must have unit tests for **precedence**, **null-skip**,
  **empty-pool**, and **merge vs first-wins** behaviour — not just the happy path — so boundary and
  conditional mutants are killed. `HandleMatcher` gets its own focused unit test (`*`, intersect,
  empty).
- **phpcs / cs-fixer**: `declare(strict_types=1)`, full docblocks, constructor property promotion as
  in the existing classes.
- **di:compile**: interfaces live in `Api/`; concrete wiring in `etc/di.xml`; no circular
  dependencies; never reference optional-module classes (`Magento\PageBuilder\*`, Hyvä) in
  always-loaded PHP type hints.

See [_roadmap.md](_roadmap.md#quality-gates--definition-of-done-every-phase) for the full gate suite
and its CI mapping.
