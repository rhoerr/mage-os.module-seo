Title: [Blocker] Fallback canonical block emits wrong canonicals — store codes dropped, duplicates self-canonicalised, fires on all pages, Host-header based

**Severity: Blocker** (sitewide wrong canonicals in common configurations)

`Block/Canonical.php:47-54` builds the canonical as `scheme://host + getPathInfo()`, and the block is attached in `view/frontend/layout/default.xml:12-14` (every page). Problems:

1. **Store codes are dropped.** With "Add Store Code to URLs" enabled, the path-info processor strips the store code from `getPathInfo()`, so every canonical points at a URL missing `/storecode/` — a different (often wrong-store/404) URL, sitewide.
2. **Duplicate URLs are legitimised.** With core `catalog/seo/product_canonical_tag` at its default (disabled), the block canonicalises `/category/product.html` **to itself**, telling search engines every duplicate category-path URL is canonical — worse than emitting nothing.
3. **Fires on non-indexable pages** — search results, cart, checkout, and account pages all get canonicals (e.g. all `/catalogsearch/result?q=…` canonicalise to the empty search page). Query strings incl. `?p=N` are dropped, so paginated pages canonicalise to page 1, against Google's guidance that paginated pages self-canonicalise.
4. **Host header, not base URL.** The URL is built from `getHttpHost()` (client-controlled) rather than the configured base URL — a cache-poisoning-shaped SEO risk on origins that don't validate Host.
5. **Fragile suppression heuristic** (`Block/Canonical.php:37-45`): the block suppresses itself if *any* page asset key starts with `http(s)://`. Any module adding a remote asset (font preload, dns-prefetch) kills the fallback canonical sitewide; it should check asset content-type `canonical` instead.

**Suggested fix:** build canonicals from the store base URL + entity canonical URL (or core `Product::getUrlModel()->getUrl()` semantics), restrict to an allowlist of page types, and detect existing canonicals by asset content type.

---
*Found during a code review assessing the module for potential Mage-OS bundling.*
