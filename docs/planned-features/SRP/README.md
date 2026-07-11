# SRP / Module-Split Analysis

Analysis of whether the single `mage-os/module-seo` package should be split into a family of
feature packages — prompted by MageOS considering "one module per feature, citing SRP."

Read in order:

1. **[00-module-split-analysis.md](00-module-split-analysis.md)** — the decision aid. Framing
   (this is a component-cohesion question, *not* SRP), the natural seams, the coupling reality, an
   honest pros/cons, and a tiered recommendation (keep a bundled kernel; break out only the
   optional + volatile features; ship a meta-package).
2. **[01-dependency-graph-and-composer.md](01-dependency-graph-and-composer.md)** — the concrete
   package graph, dependency edges (hard `require` vs. soft `suggest`), `module.xml` sequencing, and
   ready-to-paste `composer.json` require blocks per package, plus a versioning policy to tame the
   matrix.
3. **[02-config-decomposition-prototype.md](02-config-decomposition-prototype.md)** — a worked
   prototype splitting `Model/Config` for one feature (Hreflang) to size the load-bearing cost,
   with a base class, the BC strategy, and an effort model extrapolated to the whole split.

**Bottom line:** don't flat-split per feature; do a tiered split (kernel + optional/volatile feature
packages + meta-package). The one prerequisite on the critical path is decomposing `Model/Config` —
start with the Hreflang pilot in doc 02 to de-risk it.
