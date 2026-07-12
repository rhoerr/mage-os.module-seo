<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Hreflang\Resolver;

use MageOS\Seo\Api\HreflangResolverInterface;
use MageOS\Seo\Model\Catalog\CurrentEntity;
use MageOS\Seo\Model\Hreflang\LinkBuilder;

/**
 * Hreflang alternates for the current category across store views.
 */
class CategoryHreflangResolver implements HreflangResolverInterface
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
        return ['catalog_category_view'];
    }

    /**
     * @inheritdoc
     */
    public function getLinks(): array
    {
        $category = $this->currentEntity->getCategory();
        if (!$category) {
            return [];
        }

        return $this->linkBuilder->build('category', (int) $category->getId());
    }
}
