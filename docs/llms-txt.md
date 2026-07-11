# AI Discoverability (llms.txt)

The module serves two plain-text documents at well-known URLs so LLM crawlers and AI agents can understand the site without full crawl cycles. This follows the emerging `llms.txt` convention.

---

## The two documents

| URL | Content | Config toggle |
|---|---|---|
| `/llms.txt` | Concise: org name, description, base URL, locale, available schema types, AI contact email | Stores → Configuration → MageOS → SEO → Enable /llms.txt |
| `/llms-full.txt` | Extended: everything in the concise version plus social profiles, full category tree with product counts, full template list | Stores → Configuration → MageOS → SEO → Enable /llms-full.txt |

Both return `404` when their respective config toggle is off.

Both config toggles are per-store-view settings.

---

## Content of /llms.txt

```
# Organisation Name
> Description tagline
> Base URL: https://example.com
> Locale: en_GB

## Key URLs
- Home: https://example.com
- Sitemap: https://example.com/sitemap.xml
- Search: https://example.com/catalogsearch/result?q={query}

## Schema types available on this site
GenericProduct, Food, Apparel, ...

## AI Contact
support@example.com
```

---

## Content of /llms-full.txt

Everything in `/llms.txt`, plus:

- Social profile URLs (from Organisation → Social profiles)
- A full schema type list (Organization, WebSite, CollectionPage, BreadcrumbList, ItemList, Product, FoodProduct, Apparel, ...)
- A full template-to-label list
- The complete category tree with product counts and URLs, indented by depth:

```
## Category Tree
- Clothing (245 products): https://example.com/clothing
  - Women's (148 products): https://example.com/clothing/womens
    - Dresses (62 products): https://example.com/clothing/womens/dresses
  - Men's (97 products): https://example.com/clothing/mens
```

- Any sections contributed by bridge modules

---

## Clean URLs

No setup is required: a custom router serves `/llms.txt`, `/llms-full.txt` and
`/llms.jsonl` directly. Do **not** add manual URL rewrites for these paths — the
internal controller URLs (`/mageos-seo/...`) 301-redirect to the canonical paths,
so a rewrite would fight the router.

---

## Generation & cache

The documents are **pre-generated to files** (default `var/mageos_seo/store_<id>/`;
configurable via `mageos_seo_general/feeds/storage_dir` for multi-server deployments
with a shared mount), mirroring core `Magento_Sitemap`. Two background processes
write them:

- the `mageosSeoFeedRegenerate` **queue consumer** rebuilds a feed group whenever it
  is invalidated (started by the default `consumers_runner` cron, or your process
  manager e.g. supervisor). Duplicate invalidations are collapsed: at most one build
  per feed group is queued at a time, and changes arriving during a build queue
  exactly one follow-up rebuild;
- the `mageos_seo_regenerate_feeds` **cron job** (nightly) does a full rebuild as a
  safety net for changes that carry no invalidation event.

Web requests **never** build the documents. When a file is missing, the controller
queues a rebuild and answers `503` with `Retry-After` until the consumer has written
it. Requests with query strings — and the internal `/mageos-seo/...` controller
URLs — are 301-redirected to the canonical path so they cannot be used to force
cache misses.

On top of the files, responses are served with `Cache-Control: public, max-age=3600`;
Varnish and the FPC cache them for one hour using the tags `MAGEOS_SEO_LLMS`
(`/llms.txt`) and `MAGEOS_SEO_LLMS_FULL` (`/llms-full.txt`).

Feeds are invalidated automatically (files deleted + FPC/Varnish purged by tag) when:

- a category is saved (`catalog_category_save_after` → `InvalidateLlmsTxtCache` observer),
- the Organisation settings are saved,
- a FAQ is saved or deleted.

To manually regenerate: run `bin/magento queue:consumers:start mageosSeoFeedRegenerate
--max-messages=10` after deleting the files, or wait for the nightly cron; flush cached
responses with `bin/magento cache:flush full_page` or a CDN/Varnish purge.

---

## Data sources

Both documents draw data from:

| Data | Source |
|---|---|
| Organisation name, description, URL, social profiles | Organisation record (store-scoped, same fallback as JSON-LD) |
| Locale | `StoreManagerInterface::getStore()->getLocaleCode()` |
| Schema template list | `SchemaBuilderPool::getAvailableTemplates()` |
| Category tree | Live `catalog_category_entity` collection, active categories only, level > 1 |
| AI contact email | `trans_email/ident_support/email` system config |

---

## Adding content from a bridge module

Register a `SectionProviderInterface` implementation in your bridge module's `di.xml`:

```php
// MyModule/Model/LlmsTxt/MySectionProvider.php
class MySectionProvider implements \MageOS\Seo\Model\LlmsTxt\SectionProviderInterface
{
    public function getConciseSection(): string
    {
        return "## Vendors\n- 42 active makers on this platform";
    }

    public function getFullSection(): string
    {
        // Return a fuller list, or '' to contribute nothing to the full document
        return "## Vendors\n" . $this->buildVendorList();
    }
}
```

```xml
<!-- MyModule/etc/di.xml -->
<type name="MageOS\Seo\Model\LlmsTxt\LlmsTxtBuilder">
    <arguments>
        <argument name="sectionProviders" xsi:type="array">
            <item name="mySection" xsi:type="object">
                MyModule\Model\LlmsTxt\MySectionProvider
            </item>
        </argument>
    </arguments>
</type>
```

Return an empty string from either method to contribute nothing to that document. Sections are appended in the order they are registered in `di.xml`.
