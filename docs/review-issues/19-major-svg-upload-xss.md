Title: [Major] Organisation logo upload allows SVG with no content validation — stored-XSS file on the media host

**Severity: Major** (security hardening)

`Controller/Adminhtml/Organisation/UploadLogo.php:52` whitelists `['jpg','jpeg','gif','png','svg']` (mirrored in `view/adminhtml/ui_component/rs_seo_organisation_form.xml:139`). `Magento\MediaStorage\Model\File\Uploader` validates extension only — no MIME/content validation and no image re-encoding — so an SVG containing `<script>` is stored under `media/mage-os/seo/logo/` (lines 20, 57-60) and served from the store domain: a stored-XSS vector. It is admin-gated, but same-origin with the storefront (and on many setups, the admin). Magento core deliberately excludes SVG from image uploaders.

Path handling itself is fine (fixed destination, core filename sanitisation, `setFilesDispersion(false)`).

**Suggested fix:** drop `svg` from the whitelist (or sanitise SVGs server-side), and add content validation for the raster types (`$uploader->addValidateCallback()` with `getimagesize`/image adapter).

Related minors: `Organisation/Save.php:86-93` stores the browser-submitted `logo_upload[0]['url']` without URL validation, and `logo_path` ends up holding an absolute URL while `etc/db_schema.xml:15` documents it as media-relative — breaks on base-URL changes/domain migrations.

---
*Found during a code review assessing the module for potential Mage-OS bundling.*
