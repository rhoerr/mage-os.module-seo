<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Catalog;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Registry;

/**
 * Single shim around the deprecated core Registry for current-entity resolution.
 *
 * Core controllers still publish current_product/current_category exclusively via the
 * Registry, so it cannot be avoided today — but isolating the deprecated dependency in
 * one class means a future replacement (or a core removal of Registry) touches this
 * class instead of every provider and resolver.
 */
class CurrentEntity
{
    /**
     * @param Registry $registry
     */
    public function __construct(
        private readonly Registry $registry
    ) {
    }

    /**
     * Return the product currently being viewed, or null outside product pages.
     *
     * @return ProductInterface|null
     */
    public function getProduct(): ?ProductInterface
    {
        $product = $this->registry->registry('current_product');
        return $product instanceof ProductInterface ? $product : null;
    }

    /**
     * Return the category currently being viewed, or null outside category pages.
     *
     * @return CategoryInterface|null
     */
    public function getCategory(): ?CategoryInterface
    {
        $category = $this->registry->registry('current_category');
        return $category instanceof CategoryInterface ? $category : null;
    }
}
