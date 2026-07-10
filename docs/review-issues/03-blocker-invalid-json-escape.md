Title: [Blocker] Invalid `\!` JSON escape corrupts the entire JSON-LD block when content contains `<!--`

**Severity: Blocker** (structured data silently lost on content-rich pages)

The `</script>`-breakout hardening in both JSON-LD emitters does:

```php
$json = str_replace(['</', '<!--'], ['<\/', '<\!--'], $json);
```

- `Model/StructuredData/Compositor.php:87`
- `Block/FaqJsonLd.php:89`

`\/` is a legal JSON escape, but **`\!` is not valid JSON**. Any product description, override value, or FAQ answer containing a literal `<!--` (Page Builder and WYSIWYG content routinely contain HTML comments; FAQ answers are emitted un-stripped at `Block/FaqJsonLd.php:71`) renders the whole `<script type="application/ld+json">` payload unparseable. Every schema on the page is silently lost and Google reports "Unparsable structured data" — on exactly the pages with rich content.

Note: the XSS defence itself is adequate (`</script>` cannot appear because `</` becomes `<\/`); this is a validity bug, not a security hole.

**Suggested fix:** encode with `JSON_HEX_TAG` (plus recommended `JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`), which encodes `<`/`>` as `<`/`>` and removes the need for both string replacements. Also reconsider `JSON_PRETTY_PRINT` (`Compositor.php:79`) which inflates page weight for no benefit.

---
*Found during a code review assessing the module for potential Mage-OS bundling.*
