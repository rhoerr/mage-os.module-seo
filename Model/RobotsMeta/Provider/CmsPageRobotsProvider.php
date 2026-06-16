<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\RobotsMeta\Provider;

use MageOS\Seo\Api\RobotsMetaProviderInterface;
use MageOS\Seo\Model\Cms\CmsPageResolver;
use MageOS\Seo\Model\Config;

/**
 * Robots meta for CMS pages: the configured CMS default.
 *
 * Closes the long-standing gap where CMS pages had no robots meta control alongside product and
 * category pages. A per-page override mechanism can later be added behind this provider without
 * changing the pool or the applier.
 */
class CmsPageRobotsProvider implements RobotsMetaProviderInterface
{
    /**
     * @param CmsPageResolver $cmsPageResolver
     * @param Config $seoConfig
     */
    public function __construct(
        private readonly CmsPageResolver $cmsPageResolver,
        private readonly Config          $seoConfig
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getHandles(): array
    {
        return ['cms_page_view'];
    }

    /**
     * @inheritdoc
     */
    public function getRobots(int $storeId): ?string
    {
        if ($this->cmsPageResolver->resolve() === null) {
            return null;
        }

        $robotsMeta = $this->seoConfig->getRobotsCmsDefault($storeId);

        return empty($robotsMeta) ? null : $robotsMeta;
    }

    /**
     * @inheritdoc
     */
    public function getSortOrder(): int
    {
        return 100;
    }
}
