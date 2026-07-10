# Review issues — drafts ready to file

Issue drafts from a code review of MageOS_Seo assessing quality, completeness, risks, and suitability
for bundling with Mage-OS (July 2026). One file per issue: 5 blockers (01–05), majors (06–30), and the
naming-consistency item (31).

Each file's first line is `Title: <issue title>`; everything after the blank line is the issue body.

## Filing them

To bulk-file against the upstream repo with the GitHub CLI:

```bash
./file-issues.sh mage-os-lab/module-seo
```

Or file manually by copy-pasting title and body.

## Index

| # | Severity | Title |
|---|----------|-------|
| 01 | Blocker | Offer-enricher and aggregate-rating DI wiring never applies |
| 02 | Blocker | Adapter getTableName() breaks table-prefix installs |
| 03 | Blocker | Invalid `\!` JSON escape corrupts JSON-LD |
| 04 | Blocker | Fallback canonical block emits wrong canonicals |
| 05 | Blocker | Default INDEX,FOLLOW overrides core design robots config |
| 06 | Major | Non-existent / non-Product schema.org types in builders |
| 07 | Major | Invalid schema.org properties in builders |
| 08 | Major | Base-currency prices labelled with display currency |
| 09 | Major | priceValidUntil fabricated; availability mapping gaps |
| 10 | Major | GTIN values unvalidated as gtin13 |
| 11 | Major | removeDefaultCanonical() can remove arbitrary page assets |
| 12 | Major | Duplicate hreflang values for stores sharing a locale |
| 13 | Major | Non-deterministic hreflang URL selection from url_rewrite |
| 14 | Major | /hreflang-sitemap.xml unbounded generation, no 50k chunking |
| 15 | Major | /llms.jsonl unbounded catalog build + N+1 queries |
| 16 | Major | /llms-full.txt category COUNT N+1 + cross-website disclosure |
| 17 | Major | Query-string cache-busting regeneration DoS |
| 18 | Major | robots.txt Allow:/ groups exempt AI bots from all disallows |
| 19 | Major | SVG logo upload — stored-XSS, no content validation |
| 20 | Major | Admin state changes via GET; no HttpPostActionInterface |
| 21 | Major | Per-store SEO overrides read/write store 0 in adminhtml |
| 22 | Major | Catalog save plugins: global scope, POST sniffing, no try/catch |
| 23 | Major | FAQ widget ttl defeats collector — FAQPage JSON-LD lost |
| 24 | Major | No IdentityInterface / FPC invalidation for FAQ content |
| 25 | Major | BreadcrumbList Hyvä-only; hardcoded makers_* handles |
| 26 | Major | composer.json missing hard requires (~8 modules) |
| 27 | Major | Deprecated Registry as current-entity mechanism |
| 28 | Major | PageTitle subsystem and CanonicalUrlManager are dead code |
| 29 | Major | Test coverage gaps; CI mutation gate 54 vs documented 75 |
| 30 | Major | Release hygiene: no tags/releases/CHANGELOG |
| 31 | Naming | rs_seo / RS_* / hyphenated table-name rename before release |
