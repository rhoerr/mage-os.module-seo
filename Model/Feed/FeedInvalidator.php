<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Feed;

use Magento\CacheInvalidate\Model\PurgeCache;
use Magento\PageCache\Model\Cache\Type as FullPageCache;
use Magento\PageCache\Model\Config as PageCacheConfig;
use MageOS\Seo\Model\Cache\CleaningMode;

/**
 * Invalidates a pre-generated feed everywhere it is cached: the var/ feed files,
 * the built-in full page cache (by tag) and Varnish (by tag purge).
 *
 * Used by the save observers and the FAQ/Organisation admin controllers so feeds
 * are regenerated promptly after content changes on every caching setup — the old
 * observers only purged Varnish, leaving built-in FPC stale until s-maxage expiry.
 */
class FeedInvalidator
{
    public const FILES_LLMS     = ['llms.txt', 'llms-full.txt'];
    public const FILES_JSONL    = ['llms.jsonl'];
    public const FILES_HREFLANG = ['hreflang-sitemap*.xml'];

    /**
     * @param FeedStorage $feedStorage
     * @param PageCacheConfig $pageCacheConfig
     * @param FullPageCache $fullPageCache
     * @param PurgeCache $purgeCache
     * @param RegenerationRequester $regenerationRequester
     * @param CleaningMode $cleaningMode
     */
    public function __construct(
        private readonly FeedStorage           $feedStorage,
        private readonly PageCacheConfig       $pageCacheConfig,
        private readonly FullPageCache         $fullPageCache,
        private readonly PurgeCache            $purgeCache,
        private readonly RegenerationRequester $regenerationRequester,
        private readonly CleaningMode          $cleaningMode
    ) {
    }

    /**
     * Invalidate the llms.txt / llms-full.txt feeds and queue their rebuild.
     *
     * @return void
     */
    public function invalidateLlms(): void
    {
        $this->invalidate(self::FILES_LLMS, ['MAGEOS_SEO_LLMS', 'MAGEOS_SEO_LLMS_FULL']);
        $this->regenerationRequester->request(FeedRegenerator::GROUP_LLMS);
    }

    /**
     * Invalidate the llms.jsonl feed and queue its rebuild.
     *
     * @return void
     */
    public function invalidateJsonl(): void
    {
        $this->invalidate(self::FILES_JSONL, ['MAGEOS_SEO_LLMS_JSONL']);
        $this->regenerationRequester->request(FeedRegenerator::GROUP_JSONL);
    }

    /**
     * Invalidate the hreflang sitemap files and queue their rebuild.
     *
     * @return void
     */
    public function invalidateHreflangSitemap(): void
    {
        $this->invalidate(self::FILES_HREFLANG, ['MAGEOS_SEO_HREFLANG_SITEMAP']);
        $this->regenerationRequester->request(FeedRegenerator::GROUP_HREFLANG);
    }

    /**
     * Delete the feed files and purge the FPC entries carrying the given tags.
     *
     * @param string[] $filePatterns
     * @param string[] $tags
     * @return void
     */
    private function invalidate(array $filePatterns, array $tags): void
    {
        foreach ($filePatterns as $pattern) {
            $this->feedStorage->deleteForAllStores($pattern);
        }

        if (!$this->pageCacheConfig->isEnabled()) {
            return;
        }

        if ((int) $this->pageCacheConfig->getType() === PageCacheConfig::VARNISH) {
            $purgeTags = [];
            foreach ($tags as $tag) {
                $purgeTags[] = \sprintf('((^|,)%s(,|$))', $tag);
            }
            $this->purgeCache->sendPurgeRequest(array_unique($purgeTags));
            return;
        }

        // Built-in FPC: clean cached documents by tag (mirrors core FlushCacheByTags).
        $this->fullPageCache->clean($this->cleaningMode->matchingAnyTag(), $tags);
    }
}
