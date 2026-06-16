<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Product\Builder;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Seo\Api\ProductSchemaBuilderInterface;
use MageOS\Seo\Model\Config;
use MageOS\Seo\Model\Product\OfferEnricher\Pool as OfferEnricherPool;
use MageOS\Seo\Model\Review\AggregateRatingResolver;
use MageOS\Seo\Service\CurrencyService;

abstract class AbstractBuilder implements ProductSchemaBuilderInterface
{
    // Standard availability URIs
    protected const AVAILABILITY_IN_STOCK   = 'https://schema.org/InStock';
    protected const AVAILABILITY_OUT        = 'https://schema.org/OutOfStock';
    protected const AVAILABILITY_PREORDER   = 'https://schema.org/PreOrder';
    protected const CONDITION_NEW           = 'https://schema.org/NewCondition';

    /**
     * @var OfferEnricherPool
     */
    protected readonly OfferEnricherPool $offerEnricherPool;

    /**
     * @var AggregateRatingResolver
     */
    protected readonly AggregateRatingResolver $aggregateRatingResolver;

    /**
     * @param StoreManagerInterface $storeManager
     * @param CurrencyService $currencyService
     * @param StockRegistryInterface $stockRegistry
     * @param ImageHelper $imageHelper
     * @param Config $seoConfig
     * @param DateTime $dateTime
     * @param OfferEnricherPool|null $offerEnricherPool
     * @param AggregateRatingResolver|null $aggregateRatingResolver
     */
    public function __construct(
        protected readonly StoreManagerInterface  $storeManager,
        protected readonly CurrencyService        $currencyService,
        protected readonly StockRegistryInterface $stockRegistry,
        protected readonly ImageHelper            $imageHelper,
        protected readonly Config                 $seoConfig,
        protected readonly DateTime               $dateTime,
        ?OfferEnricherPool $offerEnricherPool = null,
        ?AggregateRatingResolver $aggregateRatingResolver = null
    ) {
        $this->offerEnricherPool       = $offerEnricherPool ?? new OfferEnricherPool();
        $this->aggregateRatingResolver = $aggregateRatingResolver ?? new AggregateRatingResolver();
    }

    /**
     * Build the shared base node present on all product schemas.
     *
     * Subclasses call this and then add their template-specific fields.
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param mixed[] $variantData
     * @return mixed[]
     */
    protected function buildBase(ProductInterface $product, array $variantData): array
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $store      = $this->storeManager->getStore();
        $baseUrl    = rtrim((string) $store->getBaseUrl(), '/');
        $productUrl = $product->getProductUrl();

        // Offers: use variant URL if a variant is active
        $offersUrl = !empty($variantData['_canonical_url'])
            ? $variantData['_canonical_url']
            : $productUrl;

        $price    = $this->resolvePrice($product, $variantData);
        $currency = $this->currencyService->getCurrentCurrencyCode();

        $schema = [
            '@context' => 'https://schema.org',
            '@type'    => $this->getSchemaType(),
            '@id'      => $productUrl . '#product',
            'name'     => $product->getName(),
            'url'      => $productUrl,
            'sku'      => $product->getSku(),
            'offers'   => [
                '@type'            => 'Offer',
                'url'              => $offersUrl,
                'price'            => $price,
                'priceCurrency'    => $currency,
                'availability'     => $this->resolveAvailability($product, $variantData),
                'itemCondition'    => self::CONDITION_NEW,
                'priceValidUntil'  => $this->getPriceValidUntil(),
            ],
        ];

        // Description
        $rawDesc = (string) $product->getShortDescription() ?: (string) $product->getDescription();
        $description = $this->stripHtml($rawDesc);
        if ($description !== '') {
            $schema['description'] = mb_substr($description, 0, 5000);
        }

        // Images
        $images = $this->getProductImages($product);
        if (!empty($images)) {
            $schema['image'] = \count($images) === 1 ? $images[0] : $images;
        }

        $storeId = (int) $store->getId();

        // AggregateOffer for products that expose a price range (e.g. configurable).
        $priceRange = $this->resolvePriceRange($product, $variantData);
        if ($priceRange !== null) {
            $schema['offers'] = $this->buildAggregateOffer($schema['offers'], $priceRange);
        }

        // Offer enrichment: shipping, returns, item condition, … (pluggable pool).
        $offerAdditions = $this->offerEnricherPool->enrich($product, $storeId);
        if (!empty($offerAdditions)) {
            $schema['offers'] = array_merge($schema['offers'], $offerAdditions);
        }

        // Product-level AggregateRating from the (pluggable) rating provider pool.
        if ($this->seoConfig->isAggregateRatingEnabled($storeId)) {
            $rating = $this->aggregateRatingResolver->resolve((int) $product->getId(), $storeId);
            if ($rating !== null) {
                $schema['aggregateRating'] = array_merge(['@type' => 'AggregateRating'], $rating);
            }
        }

        return $schema;
    }

    /**
     * Resolve a low/high price range for products that have one (configurable), or null.
     *
     * Returns null for single-variant requests and any product whose final price does not expose a
     * usable minimal/maximal range, so the standard single Offer is kept.
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param mixed[] $variantData
     * @return array{low: float, high: float}|null
     */
    protected function resolvePriceRange(ProductInterface $product, array $variantData): ?array
    {
        if (!empty($variantData)) {
            return null;
        }
        if ($product->getTypeId() !== 'configurable') {
            return null;
        }

        try {
            /** @var \Magento\Catalog\Model\Product $product */
            $finalPrice = $product->getPriceInfo()->getPrice('final_price');
            if (!method_exists($finalPrice, 'getMinimalPrice') || !method_exists($finalPrice, 'getMaximalPrice')) {
                return null;
            }
            $min = (float) $finalPrice->getMinimalPrice()->getValue();
            $max = (float) $finalPrice->getMaximalPrice()->getValue();
        } catch (\Throwable) {
            return null;
        }

        if ($min <= 0.0 || $max <= 0.0 || $min >= $max) {
            return null;
        }

        return ['low' => $min, 'high' => $max];
    }

    /**
     * Convert a single Offer node into an AggregateOffer with low/high price.
     *
     * Preserves currency, availability, url and any other Offer fields; replaces the scalar price
     * with lowPrice/highPrice.
     *
     * @param mixed[] $offer
     * @param float[] $range
     * @return mixed[]
     */
    protected function buildAggregateOffer(array $offer, array $range): array
    {
        $offer['@type'] = 'AggregateOffer';
        unset($offer['price']);
        $offer['lowPrice']  = number_format($range['low'], 2, '.', '');
        $offer['highPrice'] = number_format($range['high'], 2, '.', '');

        return $offer;
    }

    /**
     * Return the schema.org @type for this builder.
     *
     * Subclasses override for specialised types (FoodProduct, Apparel, etc.).
     *
     * @return string
     */
    protected function getSchemaType(): string
    {
        return 'Product';
    }

    /**
     * Resolve the scalar price value, preferring active variant price.
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param mixed[] $variantData
     * @return string
     */
    protected function resolvePrice(ProductInterface $product, array $variantData): string
    {
        /** @var \Magento\Catalog\Model\Product $product */
        if (!empty($variantData['_price'])) {
            return number_format((float) $variantData['_price'], 2, '.', '');
        }
        $finalPrice = $product->getPriceInfo()->getPrice('final_price')->getValue();
        return number_format((float) $finalPrice, 2, '.', '');
    }

    /**
     * Resolve schema.org availability URI.
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param mixed[] $variantData
     * @return string
     */
    protected function resolveAvailability(ProductInterface $product, array $variantData): string
    {
        if (!empty($variantData['_availability'])) {
            return $variantData['_availability'];
        }
        try {
            $stock = $this->stockRegistry->getStockItem((int) $product->getId());
            if ($stock->getIsInStock()) {
                return self::AVAILABILITY_IN_STOCK;
            }
        } catch (\Exception) { // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch -- fall through to default
        }
        return self::AVAILABILITY_OUT;
    }

    /**
     * Read a product attribute value safely, returning empty string if unset.
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param string $code
     * @return string
     */
    protected function attr(ProductInterface $product, string $code): string
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $value = $product->getData($code);
        if ($value === null || $value === false || $value === '') {
            return '';
        }
        // For select attributes, resolve label
        if (is_numeric($value)) {
            try {
                $label = $product->getAttributeText($code);
                if (\is_string($label) && $label !== '') {
                    return $label;
                }
            } catch (\Exception) { // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
            }
        }
        return (string) $value;
    }

    /**
     * Apply overrides to a schema node. Override keys map directly to top-level schema properties.
     *
     * @param mixed[] $schema
     * @param mixed[] $overrides
     * @return mixed[]
     */
    protected function applyOverrides(array $schema, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            if ($value !== null && $value !== '') {
                $schema[$key] = $value;
            }
        }
        return $schema;
    }

    /**
     * Compute priceValidUntil as end-of-day N months from today.
     *
     * @return string ISO 8601 date string (Y-m-d)
     */
    protected function getPriceValidUntil(): string
    {
        $storeId = (int) $this->storeManager->getStore()->getId();
        $months  = $this->seoConfig->getPriceValidUntilMonths($storeId);
        return date('Y-m-d', (int) strtotime("+{$months} months"));
    }

    /**
     * Strip HTML tags and decode entities for use in schema text fields.
     *
     * @param string $html
     * @return string
     */
    protected function stripHtml(string $html): string
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * Return product image URLs for the schema image field.
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return string[]
     */
    protected function getProductImages(ProductInterface $product): array
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $images = [];
        try {
            $mediaGallery = $product->getMediaGalleryImages();
            if ($mediaGallery) {
                foreach ($mediaGallery as $image) {
                    $url = (string) $image->getUrl();
                    if ($url !== '') {
                        $images[] = $url;
                    }
                    if (\count($images) >= 5) {
                        break;
                    }
                }
            }
        } catch (\Exception) { // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
        }

        if (empty($images)) {
            try {
                $url = (string) $this->imageHelper
                    ->init($product, 'product_page_image_large')
                    ->getUrl();
                if ($url !== '') {
                    $images[] = $url;
                }
            } catch (\Exception) { // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch -- no image available
            }
        }

        return $images;
    }
}
