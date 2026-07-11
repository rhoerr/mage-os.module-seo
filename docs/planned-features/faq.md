# Planned Feature: FAQ Subsystem

**Status:** Planned (Phase 3). Supersedes Feature 1 of [multistore-aeo.md](multistore-aeo.md).
**Complexity:** High (data + admin UI + widget + Page Builder + schema parity).
**Priority driver:** `FAQPage` rich results and "People Also Ask" are the most direct AEO signal,
and FAQs are high-value LLM training/answer content. The hard part is **keeping FAQ structured data
in parity with what is visibly rendered**, because Google requires `FAQPage` schema to match visible
on-page content — and FAQs can be placed anywhere via widgets or Page Builder.

There is **no existing FAQ system** in the module; everything here is net-new, including
`AbstractFaqElement`.

## Governing constraint (why a collector)

- JSON-LD renders from `Block/JsonLd` wired into `head.additional` (`view/frontend/layout/default.xml`)
  — i.e. **before** `<body>`.
- Widgets and Page Builder elements render **in the body**, expanded by the template filter
  (`Magento\PageBuilder\Plugin\Filter\TemplatePlugin` → `Model\Filter\Template`).
- Therefore head-time code cannot observe a body-placed FAQ element on the same request.

Solution: a **request-scoped collector** spanning head + body (same lifecycle as
`Model/Product/SchemaRegistry`, a default-shared DI singleton) plus a **late end-of-body emitter**.
Only visibly-rendered FAQs become schema → parity by construction.

## Dependency reality (verified against the full install)

Verified in `/home/mark/completely.frankenphp/src/vendor`:

- **STANDARD — assume present:** `Magento_Widget`, `Magento_Cms`, `Magento_PageBuilder`.
- **OPTIONAL — must NOT depend on:** the **Hyvä theme**, and `mage-os/module-page-builder-widget`
  (the generic widget→PB "CMS Widget" content type).

Consequences:

1. We cannot rely on the optional widget→PB bridge to surface a FAQ widget inside Page Builder.
2. Output must be **theme-agnostic** (active dev theme is Hyvä, but Luma must work too).

## Design: build BOTH placements + a theme-agnostic renderer

- **FAQ Widget** (`etc/widget.xml` + block) — universal classic placement (layout XML, widget
  instances, `{{widget}}` in any CMS/PB content), works on any theme.
- **Native FAQ Page Builder content type** — because PB is standard and the widget bridge is
  optional, ship our own content type so PB authors get a drag-drop FAQ element without the bridge.
  Pattern: PB `tabs` / `tab_item` (repeatable Q&A items) — see
  `vendor/mage-os/module-page-builder/view/adminhtml/pagebuilder/content_type/`.
- **Default renderer = `<details>/<summary>`, no JS** — identical function on Luma, Hyvä and inside
  PB, with zero CSP-nonce friction. An optional Hyvä Alpine/Tailwind template is polish delivered via
  Hyvä's theme fallback, **not** required for the feature to work.

Coverage matrix (all green):

| Frontend | PB-widget bridge | Visible via | Schema |
|----------|------------------|-------------|--------|
| Luma | present/absent | Widget + native PB type | ✅ |
| Hyvä | present/absent | Widget (`<details>`) + native PB type | ✅ |

## Interface stack (the latch points)

```text
Api/Data/FaqInterface              // question, answer, sort_order
Api/Data/FaqGroupInterface         // named reusable set: id, identifier, faqs[], store_id
Api/FaqGroupRepositoryInterface    // getById / getByIdentifier / save / delete / getList

Api/FaqSourceProviderInterface (POOL)         // schema + llms.txt; declarative
    getHandles(): array
    getFaqs(FaqContext $ctx): FaqInterface[]
  built-ins: TableFaqSource (global/entity-bound), ProductAttributeFaqSource

Api/FaqRendererInterface           renderHtml(FaqInterface[] $faqs, array $opts): string  // one renderer

Api/FaqCollectorInterface          // request-scoped singleton (SchemaRegistry analogue)
    collect(string $key, string[] $groupIdentifiers): void   // stores IDENTIFIERS (see cache hazard)
    markEmittedInHead(string[] $identities): void
    isEmittedInHead(string $identity): bool
    getCollectedGroupIdentifiers(): string[]

Api/FaqElementInterface            // THE LATCH for widgets / PB / any element
    getFaqGroupIdentifier(): ?string
    getInlineFaqs(): array
    getRenderOptions(): array
Block/AbstractFaqElement           // template method: resolve(group|inline) → render → collect
  ├─ Block/Widget/FaqList          // extends AbstractFaqElement, implements Widget\BlockInterface
  └─ Block/PageBuilder/Faq         // extends AbstractFaqElement (native PB content type render block)

Model/StructuredData/Provider/FaqSchemaProvider  // head path: entity/global FAQs, seeds collector
Block/FaqJsonLd  (before.body.end)               // late path: re-resolve collected ids, set-diff vs emittedInHead
Model/LlmsTxt/FaqLlmsSectionProvider             // reads source pool directly (separate request, no collector)
```

`FaqSourceProviderInterface` rides the generic pool pattern
([architecture-provider-pools.md](architecture-provider-pools.md)); the renderer / collector /
element interfaces are FAQ-specific latch points other modules implement to contribute visible FAQs.

## Schema emission — HYBRID (decided)

- **Head path:** `FaqSchemaProvider` (a `StructuredDataProviderInterface`) resolves entity/global
  FAQs from the source pool, emits a `FAQPage` node **and** seeds the collector with their
  identities tagged `emittedInHead`. Identity = `groupIdentifier + sha1(normalised question)`.
- **Body path:** widget/PB/entity elements call `collect()` with their declared **group
  identifiers** as they render.
- **Late path:** `Block/FaqJsonLd` in `before.body.end` re-resolves the collected group identifiers
  to FAQs and emits a second `FAQPage` containing only identities **not** already `emittedInHead`
  (set difference) → no duplicate questions across the two nodes.
- **Parity guard:** the module ships visible entity FAQ blocks (product/category FAQ sections) so
  head-emitted entity FAQs always have matching visible content; config
  `faq/entity_schema_requires_visible` (default on) suppresses head emission when the visible block
  is absent from the layout.

> Single-node fallback (if two `FAQPage` nodes are undesirable): the head path only *seeds* the
> collector and emits nothing; the late block emits one consolidated node. The machinery supports
> both; default is the hybrid above.

## Block-cache hazard (load-bearing design point)

The `{{widget}}` directive renders by calling the block's `toHtml`. If that block is HTML-cached (or
ESI-holepunched), a cache hit returns markup **without** re-running the block, so a `collect()`
side-effect would be lost and the late schema would drift from the visible FAQ.

**Mitigation (mandatory):** the collector stores **declared group identifiers**, and the late
emitter **re-resolves** group → FAQs from the repository. Combined with full-page cache (which
caches visible HTML + late schema together), this keeps schema and content in parity regardless of
block/ESI caching.

## Data model

New table `mageos_seo_faq` (+ optional `mageos_seo_faq_group`) following the schema in
[multistore-aeo.md](multistore-aeo.md) Feature 1 (entity_id, question, answer, store_id, page_type,
page_id, sort_order, is_active). Register columns in `etc/db_schema_whitelist.json`. Store scoping:
`store_id=0` = all stores; `store_id=N` overrides for that store view — same convention as
`mageos_seo_category_config`.

Admin: grid + form UI components, ACL resource, menu item under **MageOS → SEO → FAQ Manager**
(pattern: existing Organisation admin UI, `Ui/DataProvider/*`, `view/adminhtml/ui_component/*`).

## New / changed files (core)

| Area | Files |
|------|-------|
| Data/repo | `Api/Data/Faq{,Group}Interface.php`, `Api/Faq{Source,GroupRepository}Interface.php`, `Model/Faq*.php`, `Model/ResourceModel/Faq*` |
| Render/parity | `Api/FaqRendererInterface.php`, `Api/FaqCollectorInterface.php`, `Api/FaqElementInterface.php`, `Model/Faq/{Collector,Renderer}.php`, `Block/AbstractFaqElement.php` |
| Sources | `Model/Faq/Source/{TableFaqSource,ProductAttributeFaqSource}.php` |
| Schema | `Model/StructuredData/Provider/FaqSchemaProvider.php`, `Block/FaqJsonLd.php`, `Block/Faq/EntityFaqSection.php` |
| Widget | `Block/Widget/FaqList.php`, `etc/widget.xml` |
| Page Builder | `Block/PageBuilder/Faq.php`, `view/adminhtml/pagebuilder/content_type/mageos_seo_faq.xml` + appearance/form/master/preview templates |
| llms | `Model/LlmsTxt/FaqLlmsSectionProvider.php` |
| Wiring | `etc/db_schema.xml` (+whitelist), `etc/di.xml`, `etc/adminhtml/{menu,acl}.xml`, `view/frontend/layout/default.xml` (`before.body.end` block), `view/frontend/templates/faq/{accordion,json-ld}.phtml`, `Model/Config.php` getters |

## Implementation-time verifications (must do)

1. **Confirm the active theme (esp. Hyvä) renders the `before.body.end` container** the late
   `FaqJsonLd` block targets — Hyvä can customise the root template. If it doesn't, choose/insert a
   container Hyvä renders.
2. PB content-type **frontend** rendering under Hyvä depends on the store's PB-compat setup; the FAQ
   **widget** path works under Hyvä regardless. Keep PB PHP coupling minimal — **no
   `Magento\PageBuilder\*` type hints** in always-loaded PHP (di:compile must pass even though PB is
   present, to keep the block reusable and the gate robust).

## Tests

- **Unit:** `Collector` (dedupe by identity, emittedInHead set-diff, identifier storage);
  `FaqSchemaProvider` (entity vs global, empty → no node); `AbstractFaqElement` (resolve group vs
  inline, calls collect once); each `FaqSource` (store scoping, empty result); renderer outputs valid
  `<details>` markup and escapes content.
- **Integration** (`@magentoAppArea frontend`): FAQ widget on a CMS page → visible accordion +
  `FAQPage` in page source, questions match; product FAQ → present on product page, absent on
  category; `store_id=0` shows on all stores, `store_id=N` only on store N; **block-cache on →
  schema still present** (re-resolution path).
- **Mutation (MSI ≥ 75):** the set-difference, dedupe, and store-scoping branches need explicit
  negative tests to kill mutants.

## Quality gates

Standard suite — see [_roadmap.md](_roadmap.md#quality-gates--definition-of-done-every-phase).
FAQ-specific: di:compile clean with zero `Magento\PageBuilder\*` type hints in always-loaded PHP;
admin UI components validate; new db_schema columns whitelisted; integration tests follow the
`@magentoAppArea` / no-`@dataProvider` / nullable-props conventions.
