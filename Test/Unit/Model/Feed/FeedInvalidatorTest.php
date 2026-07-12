<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Feed;

use Magento\CacheInvalidate\Model\PurgeCache;
use Magento\PageCache\Model\Cache\Type as FullPageCache;
use Magento\PageCache\Model\Config as PageCacheConfig;
use MageOS\Seo\Model\Cache\CleaningMode;
use MageOS\Seo\Model\Feed\FeedInvalidator;
use MageOS\Seo\Model\Feed\FeedRegenerator;
use MageOS\Seo\Model\Feed\FeedStorage;
use MageOS\Seo\Model\Feed\RegenerationRequester;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FeedInvalidatorTest extends TestCase
{
    /**
     * @var FeedStorage&MockObject
     */
    private FeedStorage&MockObject $feedStorage;

    /**
     * @var PageCacheConfig&MockObject
     */
    private PageCacheConfig&MockObject $pageCacheConfig;

    /**
     * @var FullPageCache&MockObject
     */
    private FullPageCache&MockObject $fullPageCache;

    /**
     * @var PurgeCache&MockObject
     */
    private PurgeCache&MockObject $purgeCache;

    /**
     * @var RegenerationRequester&MockObject
     */
    private RegenerationRequester&MockObject $regenerationRequester;

    private FeedInvalidator $invalidator;

    protected function setUp(): void
    {
        $this->feedStorage           = $this->createMock(FeedStorage::class);
        $this->pageCacheConfig       = $this->createMock(PageCacheConfig::class);
        $this->fullPageCache         = $this->createMock(FullPageCache::class);
        $this->purgeCache            = $this->createMock(PurgeCache::class);
        $this->regenerationRequester = $this->createMock(RegenerationRequester::class);

        $this->invalidator = new FeedInvalidator(
            $this->feedStorage,
            $this->pageCacheConfig,
            $this->fullPageCache,
            $this->purgeCache,
            $this->regenerationRequester,
            new CleaningMode()
        );
    }

    public function testInvalidateLlmsDeletesFilesAndPurgesVarnishTags(): void
    {
        $this->pageCacheConfig->method('isEnabled')->willReturn(true);
        $this->pageCacheConfig->method('getType')->willReturn((string) PageCacheConfig::VARNISH);

        $this->feedStorage->expects($this->exactly(2))->method('deleteForAllStores');
        $this->purgeCache
            ->expects($this->once())
            ->method('sendPurgeRequest')
            ->with([
                '((^|,)MAGEOS_SEO_LLMS(,|$))',
                '((^|,)MAGEOS_SEO_LLMS_FULL(,|$))',
            ]);
        $this->fullPageCache->expects($this->never())->method('clean');

        $this->invalidator->invalidateLlms();
    }

    public function testInvalidateLlmsCleansBuiltInFpcByTag(): void
    {
        $this->pageCacheConfig->method('isEnabled')->willReturn(true);
        $this->pageCacheConfig->method('getType')->willReturn((string) PageCacheConfig::BUILT_IN);

        $this->purgeCache->expects($this->never())->method('sendPurgeRequest');
        // Literal on purpose: 'matchingAnyTag' is the cross-version contract the
        // cache backend receives (CacheConstants on 2.4.9+, Zend_Cache before).
        $this->fullPageCache
            ->expects($this->once())
            ->method('clean')
            ->with(
                'matchingAnyTag',
                ['MAGEOS_SEO_LLMS', 'MAGEOS_SEO_LLMS_FULL']
            );

        $this->invalidator->invalidateLlms();
    }

    public function testFilesAreDeletedEvenWhenPageCacheDisabled(): void
    {
        $this->pageCacheConfig->method('isEnabled')->willReturn(false);

        $this->feedStorage->expects($this->once())->method('deleteForAllStores')->with('llms.jsonl');
        $this->purgeCache->expects($this->never())->method('sendPurgeRequest');
        $this->fullPageCache->expects($this->never())->method('clean');

        $this->invalidator->invalidateJsonl();
    }

    public function testInvalidateHreflangSitemapUsesChunkAwarePattern(): void
    {
        $this->pageCacheConfig->method('isEnabled')->willReturn(false);

        $this->feedStorage
            ->expects($this->once())
            ->method('deleteForAllStores')
            ->with('hreflang-sitemap*.xml');

        $this->invalidator->invalidateHreflangSitemap();
    }

    public function testInvalidateJsonlCleansBuiltInFpcWithTheJsonlTag(): void
    {
        $this->pageCacheConfig->method('isEnabled')->willReturn(true);
        $this->pageCacheConfig->method('getType')->willReturn((string) PageCacheConfig::BUILT_IN);

        $this->fullPageCache
            ->expects($this->once())
            ->method('clean')
            ->with('matchingAnyTag', ['MAGEOS_SEO_LLMS_JSONL']);

        $this->invalidator->invalidateJsonl();
    }

    public function testInvalidateHreflangCleansBuiltInFpcWithTheHreflangTag(): void
    {
        $this->pageCacheConfig->method('isEnabled')->willReturn(true);
        $this->pageCacheConfig->method('getType')->willReturn((string) PageCacheConfig::BUILT_IN);

        $this->fullPageCache
            ->expects($this->once())
            ->method('clean')
            ->with('matchingAnyTag', ['MAGEOS_SEO_HREFLANG_SITEMAP']);

        $this->invalidator->invalidateHreflangSitemap();
    }

    public function testEachInvalidationQueuesTheMatchingRebuild(): void
    {
        $this->pageCacheConfig->method('isEnabled')->willReturn(false);

        $requested = [];
        $this->regenerationRequester->method('request')->willReturnCallback(
            function (string $group) use (&$requested): void {
                $requested[] = $group;
            }
        );

        $this->invalidator->invalidateLlms();
        $this->invalidator->invalidateJsonl();
        $this->invalidator->invalidateHreflangSitemap();

        $this->assertSame(
            [FeedRegenerator::GROUP_LLMS, FeedRegenerator::GROUP_JSONL, FeedRegenerator::GROUP_HREFLANG],
            $requested
        );
    }
}
