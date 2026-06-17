<?php

declare(strict_types=1);

namespace MageOS\Seo\Observer;

use Magento\CacheInvalidate\Model\PurgeCache;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\PageCache\Model\Config;

/**
 * Purges only the /llms.jsonl FPC tag when a product changes.
 *
 * Kept separate from the llms.txt/llms-full.txt invalidation so a product save does not
 * needlessly purge the narrative documents (which depend on org/category data, not products).
 * When Varnish is off, the response max-age handles freshness.
 */
class InvalidateLlmsJsonlCache implements ObserverInterface
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
     * Purge the llms.jsonl cache tag on product save.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        if ((int) $this->config->getType() === Config::VARNISH && $this->config->isEnabled()) {
            $this->purgeCache->sendPurgeRequest(['((^|,)RS_LLMS_JSONL(,|$))']);
        }
    }
}
