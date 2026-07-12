<?php

declare(strict_types=1);

namespace MageOS\Seo\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MageOS\Seo\Model\Feed\FeedInvalidator;

/**
 * Invalidates the hreflang sitemap files and cached responses when catalogue, CMS or
 * store data changes so the sitemap is regenerated on the next request or cron run.
 */
class InvalidateHreflangSitemapCache implements ObserverInterface
{
    /**
     * @param FeedInvalidator $feedInvalidator
     */
    public function __construct(
        private readonly FeedInvalidator $feedInvalidator
    ) {
    }

    /**
     * Invalidate the hreflang sitemap on relevant entity/store changes.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $this->feedInvalidator->invalidateHreflangSitemap();
    }
}
