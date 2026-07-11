<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\RobotsMeta\Provider;

use MageOS\Seo\Api\RobotsMetaProviderInterface;
use MageOS\Seo\Model\Catalog\CurrentEntity;
use MageOS\Seo\Model\Category\ProductOverrideRepository;
use MageOS\Seo\Model\Config;

/**
 * Robots meta for product pages: per-product override, falling back to the configured default.
 */
class ProductRobotsProvider implements RobotsMetaProviderInterface
{
    /**
     * @param CurrentEntity $currentEntity
     * @param ProductOverrideRepository $productOverrideRepository
     * @param Config $seoConfig
     */
    public function __construct(
        private readonly CurrentEntity $currentEntity,
        private readonly ProductOverrideRepository $productOverrideRepository,
        private readonly Config                    $seoConfig
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
    public function getRobots(int $storeId): ?string
    {
        $product = $this->currentEntity->getProduct();
        if (!$product) {
            return null;
        }

        $override   = $this->productOverrideRepository->getForProduct((int) $product->getId(), $storeId);
        $robotsMeta = $override['robots_meta'] ?? null;

        if (empty($robotsMeta)) {
            $robotsMeta = $this->seoConfig->getRobotsProductDefault($storeId);
        }

        return empty($robotsMeta) ? null : (string) $robotsMeta;
    }

    /**
     * @inheritdoc
     */
    public function getSortOrder(): int
    {
        return 100;
    }
}
