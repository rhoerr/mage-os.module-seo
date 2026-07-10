Title: [Major] Release hygiene: no tags, no releases, no CHANGELOG; version hardcoded in composer.json

**Severity: Major** (distribution readiness)

- `composer.json` declares `"version": "1.1.0"`, but the repository has **no git tags** — the version is unverifiable against any release, and hardcoding `version` in composer.json is itself an anti-pattern for VCS/packagist distribution (the tag should be the source of truth).
- No CHANGELOG, no GitHub releases, no release automation (a `# x-release-please-version` comment hints at intent, but no release-please workflow exists).
- The `repositories` entry pointing at mirror.mage-os.org in a published package affects consumers' resolution when installed as a path/VCS dependency — worth trimming from the distributed package.

**Suggested fix:** remove the `version` field, tag releases (v1.1.0 onward), add a CHANGELOG (or wire up release-please, which the CI comment suggests was planned), and publish GitHub releases so downstream consumers can pin and audit upgrades.

---
*Found during a code review assessing the module for potential Mage-OS bundling.*
