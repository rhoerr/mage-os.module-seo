<?php

declare(strict_types=1);

namespace MageOS\Seo\Observer;

use Magento\CacheInvalidate\Model\PurgeCache;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\PageCache\Model\Config;

/**
 * Purges the cached /hreflang-sitemap.xml when catalogue, CMS or store data changes so the sitemap
 * is regenerated on the next request. When Varnish is off, the response max-age handles freshness.
 */
class InvalidateHreflangSitemapCache implements ObserverInterface
{
    /**
     * @param Config $config
     * @param PurgeCache $purgeCache
     */
    public function __construct(
        private readonly Config     $config,
        private readonly PurgeCache $purgeCache
    ) {
    }

    /**
     * Purge the hreflang sitemap cache tag on relevant entity/store changes.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        if ((int) $this->config->getType() === Config::VARNISH && $this->config->isEnabled()) {
            $this->purgeCache->sendPurgeRequest(['((^|,)RS_HREFLANG_SITEMAP(,|$))']);
        }
    }
}
