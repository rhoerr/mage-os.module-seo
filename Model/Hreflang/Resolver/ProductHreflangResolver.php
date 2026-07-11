<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Hreflang\Resolver;

use MageOS\Seo\Api\HreflangResolverInterface;
use MageOS\Seo\Model\Catalog\CurrentEntity;
use MageOS\Seo\Model\Hreflang\LinkBuilder;

/**
 * Hreflang alternates for the current product across store views.
 */
class ProductHreflangResolver implements HreflangResolverInterface
{
    /**
     * @param CurrentEntity $currentEntity
     * @param LinkBuilder $linkBuilder
     */
    public function __construct(
        private readonly CurrentEntity $currentEntity,
        private readonly LinkBuilder $linkBuilder
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
    public function getLinks(): array
    {
        $product = $this->currentEntity->getProduct();
        if (!$product) {
            return [];
        }

        return $this->linkBuilder->build('product', (int) $product->getId());
    }
}
