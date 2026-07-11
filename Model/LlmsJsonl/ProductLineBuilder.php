<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\LlmsJsonl;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Seo\Service\CurrencyService;

/**
 * Builds a compact JSON-LD Product node (one line of /llms.jsonl) for a single product.
 *
 * Deliberately leaner than the full product schema builders: just the fields an AI catalog consumer
 * needs. Omits empty values to keep each line small.
 */
class ProductLineBuilder
{
    private const AVAILABILITY_IN_STOCK = 'https://schema.org/InStock';
    private const AVAILABILITY_OUT      = 'https://schema.org/OutOfStock';

    /**
     * @param StoreManagerInterface $storeManager
     * @param CurrencyService $currencyService
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly CurrencyService       $currencyService
    ) {
    }

    /**
     * Build the JSON-LD Product node for a product.
     *
     * @param ProductInterface $product
     * @return array<string, mixed>
     */
    public function build(ProductInterface $product): array
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $url = $product->getProductUrl();

        $node = [
            '@context' => 'https://schema.org',
            '@type'    => 'Product',
            '@id'      => $url,
            'name'     => (string) $product->getName(),
            'url'      => $url,
            'offers'   => [
                '@type'         => 'Offer',
                'price'         => $this->price($product),
                'priceCurrency' => $this->currencyService->getCurrentCurrencyCode(),
                'availability'  => $product->isSalable() ? self::AVAILABILITY_IN_STOCK : self::AVAILABILITY_OUT,
                'url'           => $url,
            ],
        ];

        $sku = (string) $product->getSku();
        if ($sku !== '') {
            $node['sku'] = $sku;
        }

        $description = $this->description($product);
        if ($description !== '') {
            $node['description'] = $description;
        }

        $image = $this->image($product);
        if ($image !== '') {
            $node['image'] = $image;
        }

        return $node;
    }

    /**
     * Resolve the final price as a formatted string.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    private function price(ProductInterface $product): string
    {
        try {
            $value = $product->getPriceInfo()->getPrice('final_price')->getValue();
        } catch (\Exception) { // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch -- fall back to 0
            $value = 0.0;
        }
        // PriceInfo amounts are base currency; convert so the amount matches the
        // display currency code emitted with it.
        return number_format($this->currencyService->convertFromBase((float) $value), 2, '.', '');
    }

    /**
     * Resolve a plain-text description (short, then full), trimmed to 300 chars.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    private function description(ProductInterface $product): string
    {
        $raw = (string) ($product->getShortDescription() ?: $product->getDescription());
        if ($raw === '') {
            return '';
        }
        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged -- plain-text from rich HTML
        $text = trim(html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        return mb_substr($text, 0, 300);
    }

    /**
     * Resolve an absolute product image URL, or empty string.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    private function image(ProductInterface $product): string
    {
        $image = (string) $product->getImage();
        if ($image === '' || $image === 'no_selection') {
            return '';
        }

        try {
            /** @var Store $store */
            $store    = $this->storeManager->getStore();
            $mediaUrl = rtrim((string) $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA), '/');
        } catch (\Exception) { // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch -- no media url
            return '';
        }

        return $mediaUrl . '/catalog/product' . $image;
    }
}
