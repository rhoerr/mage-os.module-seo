# Config Decomposition Prototype (Hreflang)

> Deliverable **(c)**. Companion to [00-module-split-analysis.md](00-module-split-analysis.md) and
> [01-dependency-graph-and-composer.md](01-dependency-graph-and-composer.md).

`Model/Config` is the load-bearing unknown for any module split: a single class, 26 getters, **24
consumers across every feature**. This document prototypes splitting it for **one** feature —
Hreflang — to size the real cost and lock the boundary design *before* anything is published or even
moved between packages.

Hreflang is the right pilot: self-contained, 5 config methods (two with non-trivial parsing), and a
small, knowable blast radius.

---

## Blast radius (measured, not estimated)

Files that call a Hreflang `Config` method today:

```
Block/Hreflang.php
Controller/Hreflangsitemap/Index.php
Model/Hreflang/AlternateBuilder.php
Model/Hreflang/StoreLocaleMap.php
```
Plus the constants/methods defined in `Model/Config.php` itself.

Unit tests that mock `Config` for Hreflang:
```
Test/Unit/Model/Hreflang/AlternateBuilderTest.php
Test/Unit/Model/Hreflang/ResolverPoolTest.php
Test/Unit/Model/Hreflang/StoreLocaleMapTest.php
```

**Total: 4 production consumers + 3 test files.** That is the per-feature cost shape.

The 5 methods to move (from `Model/Config.php`):
`isHreflangEnabled`, `getHreflangXDefaultStoreId`, `getHreflangExcludedStoreIds`,
`isHreflangLanguageOnlyEnabled`, `isHreflangSitemapEnabled` — plus their 5 `XML_HREFLANG_*`
constants.

---

## Step 1 — a tiny base to kill the boilerplate

Every getter is the same shape: `scopeConfig->getValue(PATH, SCOPE_STORE, $storeId)` + a cast. When
each feature owns its own config class, we don't want that boilerplate copy-pasted 12 times. Extract
it once into the **kernel** as an abstract base (or trait). This class is the only Config code that
stays in the kernel.

```php
<?php
declare(strict_types=1);

namespace MageOS\Seo\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Base for per-feature scoped-config readers. Each feature module extends this with its own
 * typed getters and config-path constants. Lives in the kernel (MageOS_Seo).
 */
abstract class AbstractScopedConfig
{
    public function __construct(
        protected readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    protected function flag(string $path, int|string|null $storeId = null): bool
    {
        return (bool) $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    protected function value(string $path, int|string|null $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /** Comma-separated → int[] (e.g. excluded store IDs). */
    protected function csvInts(string $path, int|string|null $storeId = null): array
    {
        $raw = $this->value($path, $storeId);
        if ($raw === '') {
            return [];
        }
        return array_values(array_filter(array_map(
            static fn (string $id): int => (int) trim($id),
            explode(',', $raw)
        )));
    }
}
```

> Decision point: **abstract base vs. trait.** A base class means feature configs share a type
> (`instanceof AbstractScopedConfig`) but can't extend anything else — fine here, config readers
> have no other parent. A trait avoids the inheritance slot but gives no shared type. Recommend the
> **abstract base** — it documents intent and the shared type is occasionally useful for generic
> tooling. Either way it lives in the kernel and is the contract features build on.

---

## Step 2 — the feature config class

Lives in the Hreflang package: `MageOS\Seo\Hreflang\Model\Config` (or keep the current namespace
`MageOS\Seo\Model\Hreflang\Config` if Hreflang stays inside the monolith during the pilot).

```php
<?php
declare(strict_types=1);

namespace MageOS\Seo\Hreflang\Model;

use MageOS\Seo\Model\Config\AbstractScopedConfig;

class Config extends AbstractScopedConfig
{
    private const XML_ENABLED        = 'mageos_seo_general/hreflang/enabled';
    private const XML_XDEFAULT_STORE = 'mageos_seo_general/hreflang/xdefault_store_id';
    private const XML_EXCLUDED       = 'mageos_seo_general/hreflang/excluded_store_ids';
    private const XML_LANGUAGE_ONLY  = 'mageos_seo_general/hreflang/language_only_enabled';
    private const XML_SITEMAP        = 'mageos_seo_general/hreflang/sitemap_enabled';

    public function isEnabled(int|string|null $storeId = null): bool
    {
        return $this->flag(self::XML_ENABLED, $storeId);
    }

    public function getXDefaultStoreId(): int
    {
        return (int) $this->scopeConfig->getValue(self::XML_XDEFAULT_STORE);
    }

    /** @return int[] */
    public function getExcludedStoreIds(): array
    {
        // global scope today; csvInts() defaults to store scope, so read raw here
        $raw = (string) $this->scopeConfig->getValue(self::XML_EXCLUDED);
        if ($raw === '') {
            return [];
        }
        return array_values(array_filter(array_map(
            static fn (string $id): int => (int) trim($id),
            explode(',', $raw)
        )));
    }

    public function isLanguageOnlyEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(self::XML_LANGUAGE_ONLY);
    }

    public function isSitemapEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(self::XML_SITEMAP);
    }
}
```

> Note the method names *shorten* (`isHreflangEnabled` → `isEnabled`) because the feature prefix is
> now carried by the class/namespace. Cleaner, but it is the rename that drives the consumer churn
> below. If you want **zero** consumer edits in the pilot, keep the old names on the new class —
> trade cosmetics for a smaller diff.

---

## Step 3 — consumer migration (one example)

`Model/Hreflang/StoreLocaleMap.php` today injects the god `Config`:

```php
// before
use MageOS\Seo\Model\Config;

public function __construct(
    private readonly Config $config,
    /* ... */
) {}

if ($this->config->isHreflangLanguageOnlyEnabled()) { /* ... */ }
```

After:

```php
// after
use MageOS\Seo\Hreflang\Model\Config as HreflangConfig;

public function __construct(
    private readonly HreflangConfig $config,
    /* ... */
) {}

if ($this->config->isLanguageOnlyEnabled()) { /* ... */ }
```

Repeat for the other 3 consumers (`Block/Hreflang`, `Controller/Hreflangsitemap/Index`,
`Model/Hreflang/AlternateBuilder`). Each is a 2-line change: the `use`/constructor type and the
method call sites. No DI XML changes — Magento autowires the concrete class by type.

Tests: the 3 unit tests swap their mocked type from `Config` to `HreflangConfig` and update the
mocked method names. Mechanical.

---

## Step 4 — backward compatibility (the part that de-risks publishing)

The danger in a published split is a **third-party bridge module** that already calls
`MageOS\Seo\Model\Config::isHreflangEnabled()`. Don't break it. Two options:

**Option A — deprecate-and-delegate (recommended for a published 1.x).** Keep the old method on the
god `Config` but have it delegate, and mark it `@deprecated`:

```php
// Model/Config.php — kept during the deprecation window
/** @deprecated Use \MageOS\Seo\Hreflang\Model\Config::isEnabled() */
public function isHreflangEnabled(int|string|null $storeId = null): bool
{
    return $this->hreflangConfig->isEnabled($storeId);
}
```
The god `Config` becomes a thin facade that delegates to the feature configs during the window, then
loses each block in the next major. Zero breakage; the facade shrinks over releases.

> Caveat: this makes the kernel `Config` *depend on* every feature config — which it must not, since
> features are optional. Resolve by making the delegating methods tolerant of an absent feature
> (inject via `Magento\Framework\ObjectManagerInterface` lazily, or only keep delegation in the
> meta-package). Simpler: only keep the facade for features that stay in the kernel; for *extracted*
> features, document the rename as a breaking change gated behind the major bump (Option B).

**Option B — clean break at a major.** Remove the methods, ship the rename in `2.0`, document in
UPGRADE.md. Honest and simple, but forces bridge authors to update on the major. Acceptable if the
bridge ecosystem is still small (it is — only `SellersSeo` / `ProductVariantUrlSeo` are referenced).

Recommendation: **Option A for kernel-resident features, Option B (major-gated) for extracted
features.** This keeps the optional-dependency rule intact while protecting the common case.

---

## What does NOT change

- **system.xml** — the config *paths* (`mageos_seo_general/hreflang/*`) stay identical, so existing
  stored config values keep working untouched. (When Hreflang becomes its own package, its
  system.xml `<group>` moves to that package's `etc/adminhtml/system.xml`, but the `<section>` shell
  stays in the kernel. Path strings never change.)
- **DI wiring** — no `di.xml` edits; autowiring resolves the concrete config class by type.
- **Behavior** — pure refactor; the integration `DiWiringTest` and the Hreflang unit suite are the
  safety net.

---

## Effort model (extrapolated to the whole split)

The Hreflang pilot is **~9 files** (1 new base + 1 new config + 4 consumers + 3 tests), all
mechanical, no behavior change. Extrapolating by each feature's `Config`-consumer count:

| Feature | Config methods | ~Consumers | Effort |
|---|---|---|---|
| Hreflang (pilot) | 5 | 4 | S |
| Robots meta | 5 | ~5 | S |
| StructuredData | 7 | ~6 | M |
| MetaTags / OG | 1 | ~4 | S |
| Llms | 3 | ~6 (controllers+observers) | M |
| AEO / Speakable | 2 | ~1 | XS |
| AI robots | 2 | ~1 | XS |
| **God-`Config` retirement** | — | shrinks each pass | — |

Total is **~40–50 small, mechanical edits** spread across features — large in count, near-zero in
risk, fully covered by the existing unit + `DiWiringTest` suites. The base class (Step 1) is written
once and pays for itself by the third feature.

**Conclusion for the go/no-go:** the Config decomposition is *tedious but low-risk and
parallelizable*, not a research problem. It does **not** block the split decision — it is a known,
sizable, mechanical cost. Do the Hreflang pilot first (a half-day) to confirm the base-class design
and the BC strategy; everything after is repetition.
