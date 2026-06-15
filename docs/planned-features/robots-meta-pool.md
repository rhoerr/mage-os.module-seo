# Planned Feature: Robots Meta Pool

**Status:** Planned (Phase 0) — retrofit + extend.
**Complexity:** Low–Medium.
**Priority driver:** Robots meta is currently hardcoded as two controller plugins, CMS pages have
no robots meta at all, and the directive vocabulary is limited to INDEX/FOLLOW combinations. This
makes robots meta the worked example for the provider-pool pattern in
[architecture-provider-pools.md](architecture-provider-pools.md).

## Today (the anti-pattern to replace)

- `Plugin/Controller/ProductRobotsMetaPlugin` and `CategoryRobotsMetaPlugin` each `afterExecute`
  read a per-entity override (`robots_meta` column) → fall back to a config default → call
  `pageConfig->setRobots()`.
- CMS pages: nothing (see the long-standing gap; this spec closes it).
- `Model/Config/Source/RobotsMeta` only offers INDEX/FOLLOW permutations.

Two near-identical plugins, no extension point, and a missing page type — exactly what the pool
pattern fixes.

## Target architecture

```text
RobotsMetaProviderInterface {
    getHandles(): array;                 // ['catalog_product_view'] etc., or ['*']
    getRobots(int $storeId): ?string;    // null = no opinion
    getSortOrder(): int;
}

Model/RobotsMeta/Resolver               // first-wins: highest sortOrder non-null value
Applier                                 // single plugin (or head mechanism) → pageConfig->setRobots()
```

Built-in providers (replace the two plugins, add the third):

| Provider | Handles | Source |
|----------|---------|--------|
| `ProductRobotsProvider` | `catalog_product_view` | `ProductOverrideRepository` `robots_meta` → `Config::getRobotsProductDefault()` |
| `CategoryRobotsProvider` | `catalog_category_view` | `ConfigRepository` `robots_meta` → `Config::getRobotsCategoryDefault()` + pagination rule (below) |
| `CmsPageRobotsProvider` | `cms_page_view` | per-page override (future) → `Config::getRobotsCmsDefault()` (new) |

A blog or vendor module adds `BlogPostRobotsProvider` / `VendorRobotsProvider` via its own di.xml at
sortOrder 200+ — no core change.

### Applier

The current `afterExecute` approach is page-type specific (it plugs the product/category view
controllers). With a pool covering arbitrary handles, prefer a single applier that runs late enough
to know the layout handles. Two acceptable options — decide at implementation:

1. **One controller plugin** on an action common to all relevant pages is not available, so keep a
   thin `afterExecute` plugin per built-in controller **but** delegate to `Resolver` (zero logic in
   the plugin). Lowest risk, mirrors today.
2. **A `head.additional` mechanism / `layout_generate_blocks_after` observer** that reads active
   handles once and applies the resolved value. Cleaner for arbitrary page types (blog/CMS) and is
   the recommended target.

Recommended: option 2 (observer on `layout_generate_blocks_after` or a small head ViewModel) so any
registered handle is covered without a controller plugin per page type. Document the chosen hook in
the implementation PR.

## CMS robots (closes the gap)

- `Config::XML_ROBOTS_CMS_DEFAULT = 'mageos_seo_general/robots_meta/cms_page_default'` + getter
  `getRobotsCmsDefault()`.
- `etc/config.xml` default `INDEX,FOLLOW`; `etc/adminhtml/system.xml` add `cms_page_default` field to
  the existing `robots_meta` group (mirror product/category fields).
- `CmsPageRobotsProvider` uses `Model/Cms/CmsPageResolver` to detect the current CMS page; a future
  per-page override mechanism can be added behind the same provider without changing callers.

## Enriched robots directives

Extend `Model/Config/Source/RobotsMeta::toOptionArray()` with modern directives so providers can
return granular values:

- crawl/index: existing INDEX/NOINDEX × FOLLOW/NOFOLLOW.
- snippet control: `max-snippet:-1`, `max-image-preview:large`, `max-video-preview:-1`.
- archive: `noarchive`.
- AI: `noai`, `noimageai` (respected by some AI crawlers; advisory).

Values are emitted verbatim into the robots meta string. Keep the source model the single authority
so the admin dropdowns and any free-text validation stay consistent.

> AI **bot-level** allow/deny (per-user-agent in robots.txt) is a separate Phase-4 concern delivered
> through this same pool — see [ucp-and-well-known.md](ucp-and-well-known.md). This section is about
> the page-level `<meta name="robots">` directives only.

## Pagination

Category pages with `?p=N` (and layered-nav params) are a duplicate-content source. The
`CategoryRobotsProvider` (and the canonical work in
[onpage-seo-foundation.md](onpage-seo-foundation.md)) decide the policy:

- **Recommended default:** self-referencing canonical per paginated URL is *not* used; instead set
  canonical to page 1 and leave `INDEX,FOLLOW` — or emit `noindex,follow` on `p>1`. Pick one and make
  it configurable (`mageos_seo_general/robots_meta/paginated_*`). Record the chosen default in the
  implementation PR; cross-link the canonical spec.

## New / changed files

| Action | File |
|--------|------|
| NEW | `Api/RobotsMetaProviderInterface.php` |
| NEW | `Model/RobotsMeta/Resolver.php` |
| NEW | `Model/RobotsMeta/Provider/{ProductRobotsProvider,CategoryRobotsProvider,CmsPageRobotsProvider}.php` |
| NEW | applier (observer/ViewModel or thin delegating plugins) |
| EDIT | `Model/Config.php` — `XML_ROBOTS_CMS_DEFAULT` + `getRobotsCmsDefault()` |
| EDIT | `Model/Config/Source/RobotsMeta.php` — enriched directives |
| EDIT | `etc/config.xml`, `etc/adminhtml/system.xml` — `cms_page_default` (+ pagination) |
| EDIT | `etc/di.xml` — register the pool + providers; remove the two old plugin bindings |
| DELETE | `Plugin/Controller/ProductRobotsMetaPlugin.php`, `CategoryRobotsMetaPlugin.php` (if option 2) |

## Tests

- **Unit:** `Resolver` (precedence ordering, null-skip, empty-pool → null); each provider
  (override wins over default, default fallback, returns null off-handle); `RobotsMeta` source
  contains the new directives.
- **Integration** (`@magentoAppArea frontend`): product / category / **CMS** pages emit the expected
  `<meta name="robots">`; per-entity override beats the config default.
- **Mutation:** the negative cases above exist specifically to kill precedence/boundary mutants
  (MSI ≥ 75).

## Quality gates

Standard suite — see [_roadmap.md](_roadmap.md#quality-gates--definition-of-done-every-phase).
Specific to this feature: removing the old plugins must keep di:compile green (drop their `<type>`
plugin entries in `etc/di.xml`); integration tests must carry `@magentoAppArea`, no `@dataProvider`,
nullable props (per `Test/Integration` conventions).
