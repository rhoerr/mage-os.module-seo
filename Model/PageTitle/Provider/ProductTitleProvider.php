<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\PageTitle\Provider;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\RequestInterface;
use MageOS\Seo\Api\PageTitleProviderInterface;
use MageOS\Seo\Model\Catalog\CurrentEntity;

class ProductTitleProvider implements PageTitleProviderInterface
{
    private const VARIANT_DATA_PARAM = 'variant_slug_data';

    /**
     * @param CurrentEntity $currentEntity
     * @param RequestInterface $request
     */
    public function __construct(
        private readonly CurrentEntity $currentEntity,
        private readonly RequestInterface $request
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getHandles(): array
    {
        return ['catalog_product_view'];
    }

    /**
     * @inheritdoc
     */
    public function getSortOrder(): int
    {
        return 100;
    }

    /**
     * @inheritdoc
     *
     * Only speaks when there is an explicit title (variant title or meta_title):
     * returning the product name here would override the merchant's meta_title,
     * which core already applies (with name as its own fallback).
     */
    public function getTitle(): string
    {
        $variantData = $this->request->getParam(self::VARIANT_DATA_PARAM, []);
        if (!empty($variantData['_title'])) {
            return (string) $variantData['_title'];
        }

        $product = $this->currentEntity->getProduct();
        /** @var Product $product */
        return $product ? (string) $product->getData('meta_title') : '';
    }
}
