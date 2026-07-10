Title: [Major] robots.txt AI directives: `Allow: /` groups exempt allowed bots from all existing User-agent:* disallows

**Severity: Major**

`Plugin/Robots/AppendAiDirectivesPlugin.php:61-67` emits, for every known AI bot **not** on the disallow list:

```
User-agent: <bot>
Allow: /
```

Under robots.txt group-matching semantics, a crawler obeys only its most specific User-agent group. So the moment this feature is enabled (off by default, `etc/config.xml:42`), GPTBot and 12+ other crawlers stop honouring the store's `User-agent: *` rules — checkout, cart, search, admin paths and anything else the merchant disallowed become crawlable by those bots.

**Suggested fix:** emit groups only for *disallowed* bots (`Disallow: /`), and for allowed bots either emit nothing (they fall through to `*`) or replicate the store's `*` rules inside each allowed group.

---
*Found during a code review assessing the module for potential Mage-OS bundling.*
