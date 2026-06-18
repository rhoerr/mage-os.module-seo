<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\RobotsMeta\Provider;

use Magento\Framework\App\RequestInterface;
use MageOS\Seo\Api\RobotsMetaProviderInterface;
use MageOS\Seo\Model\Config;

/**
 * Robots meta for paginated category listing pages (?p=N, N > 1).
 *
 * Opt-in (disabled by default): when enabled, paginated pages receive a dedicated robots value
 * (commonly NOINDEX,FOLLOW) so thin/duplicate paginated URLs stay out of the index while remaining
 * crawlable. Runs at a higher sortOrder than CategoryRobotsProvider so it wins on page 2+, and
 * returns null on page 1 (and when disabled) so the normal category default applies.
 *
 * Canonicalisation of paginated pages is intentionally left to Magento's native
 * catalog/seo/category_canonical_tag to avoid conflicting canonical signals.
 */
class CatalogPaginationRobotsProvider implements RobotsMetaProviderInterface
{
    /**
     * @param RequestInterface $request
     * @param Config $seoConfig
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly Config           $seoConfig
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
    public function getRobots(int $storeId): ?string
    {
        if (!$this->seoConfig->isPaginatedRobotsEnabled($storeId)) {
            return null;
        }

        if ((int) $this->request->getParam('p') <= 1) {
            return null;
        }

        $robots = $this->seoConfig->getRobotsPaginated($storeId);

        return empty($robots) ? null : $robots;
    }

    /**
     * @inheritdoc
     */
    public function getSortOrder(): int
    {
        return 200;
    }
}
