Title: [Major] GTIN values passed through unvalidated as gtin13

**Severity: Major** (Search Console "Invalid value for gtin13" errors)

All product builders map free-form `barcode`/`ean` attribute values straight to `gtin13` (e.g. `Model/Product/Builder/GenericProductBuilder.php:66`, `ApparelBuilder.php:63-68`). There is no length or checksum guard anywhere, so a 12-digit UPC, an internal SKU, or junk data is emitted as `gtin13` and produces per-product errors in Search Console.

**Suggested fix:** validate length (8/12/13/14) and the GS1 check digit; emit the appropriate property (`gtin8`/`gtin12`/`gtin13`/`gtin14`, or generic `gtin`) and omit the property entirely when the value doesn't validate.

---
*Found during a code review assessing the module for potential Mage-OS bundling.*
