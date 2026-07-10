Title: [Blocker] Offer-enricher and aggregate-rating DI wiring never applies — merchant policies and aggregateRating are never emitted

**Severity: Blocker** (advertised features silently non-functional)

`Model/Product/Builder/AbstractBuilder.php:53-57` declares the enricher/rating collaborators as optional constructor args with `new` fallbacks:

```php
?OfferEnricherPool $offerEnricherPool = null,
?AggregateRatingResolver $aggregateRatingResolver = null
```

Magento's ObjectManager passes the default (`null`) for optional constructor parameters unless di.xml explicitly configures the argument (see `Magento\Framework\ObjectManager\Factory\AbstractFactory::getResolvedArgument()`). No builder and no di.xml entry supplies these arguments, so **all 16 product schema builders receive empty pools**. The wiring at `etc/di.xml:252-272` (ItemConditionEnricher, ReturnPolicyEnricher, ShippingDetailsEnricher, NativeAggregateRatingProvider) never executes.

**Impact:** merchant shipping/return policy enrichment and `aggregateRating` are never emitted in production, despite the README ("Merchant policies… required for Google Merchant free listings", "Aggregate ratings") and the entire `mageos_seo_merchant` admin configuration section. Unit tests pass the pools explicitly, so the suite doesn't catch it; `Test/Integration/Model/Product/SchemaBuilderPoolTest.php` only checks template registration.

The same `?X $x = null` + `new X()` pattern exists in 8+ other classes (`Model/StructuredData/Compositor.php:29-31`, `Model/Hreflang/ResolverPool.php:42-46`, `Model/RobotsMeta/Resolver.php:32-35`, `Model/PageTitle/Compositor.php:26-28`, `Model/StructuredData/Provider/OrganizationProvider.php:27-29`, `ArticleSchemaProvider.php:40`, `EventSchemaProvider.php:39`). Those happen to be harmless today (the DI-configured instance is equivalent to the fallback) but are the same landmine.

**Suggested fix:** make the pool/resolver constructor arguments required everywhere and update tests to inject them; add an integration assertion that the DI-built `GenericProductBuilder` actually contains the configured enrichers/providers.

---
*Found during a code review assessing the module for potential Mage-OS bundling.*
