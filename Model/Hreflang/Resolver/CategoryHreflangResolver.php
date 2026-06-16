<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Hreflang\Resolver;

use Magento\Framework\Registry;
use MageOS\Seo\Api\HreflangResolverInterface;
use MageOS\Seo\Model\Hreflang\LinkBuilder;

/**
 * Hreflang alternates for the current category across store views.
 */
class CategoryHreflangResolver implements HreflangResolverInterface
{
    /**
     * @param Registry $registry
     * @param LinkBuilder $linkBuilder
     */
    public function __construct(
        private readonly Registry    $registry,
        private readonly LinkBuilder $linkBuilder
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getHandles(): array
    {
        return ['catalog_category_view'];
    }

    /**
     * @inheritdoc
     */
    public function getLinks(): array
    {
        $category = $this->registry->registry('current_category');
        if (!$category) {
            return [];
        }

        return $this->linkBuilder->build('category', (int) $category->getId());
    }
}
