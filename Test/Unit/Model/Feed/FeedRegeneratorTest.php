<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Feed;

use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Seo\Model\Config;
use MageOS\Seo\Model\Feed\FeedRegenerator;
use MageOS\Seo\Model\Feed\FeedStorage;
use MageOS\Seo\Model\Hreflang\SitemapGenerator;
use MageOS\Seo\Model\Hreflang\StoreLocaleMap;
use MageOS\Seo\Model\LlmsJsonl\JsonlBuilder;
use MageOS\Seo\Model\LlmsTxt\LlmsTxtBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class FeedRegeneratorTest extends TestCase
{
    /**
     * @var StoreManagerInterface&MockObject
     */
    private StoreManagerInterface&MockObject $storeManager;

    /**
     * @var Emulation&MockObject
     */
    private Emulation&MockObject $emulation;

    /**
     * @var Config&MockObject
     */
    private Config&MockObject $seoConfig;

    /**
     * @var LlmsTxtBuilder&MockObject
     */
    private LlmsTxtBuilder&MockObject $llmsTxtBuilder;

    /**
     * @var JsonlBuilder&MockObject
     */
    private JsonlBuilder&MockObject $jsonlBuilder;

    /**
     * @var SitemapGenerator&MockObject
     */
    private SitemapGenerator&MockObject $sitemapGenerator;

    /**
     * @var StoreLocaleMap&MockObject
     */
    private StoreLocaleMap&MockObject $storeLocaleMap;

    /**
     * @var FeedStorage&MockObject
     */
    private FeedStorage&MockObject $feedStorage;

    /**
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        $this->storeManager     = $this->createMock(StoreManagerInterface::class);
        $this->emulation        = $this->createMock(Emulation::class);
        $this->seoConfig        = $this->createMock(Config::class);
        $this->llmsTxtBuilder   = $this->createMock(LlmsTxtBuilder::class);
        $this->jsonlBuilder     = $this->createMock(JsonlBuilder::class);
        $this->sitemapGenerator = $this->createMock(SitemapGenerator::class);
        $this->storeLocaleMap   = $this->createMock(StoreLocaleMap::class);
        $this->feedStorage      = $this->createMock(FeedStorage::class);
        $this->logger           = $this->createMock(LoggerInterface::class);
    }

    private function regenerator(): FeedRegenerator
    {
        return new FeedRegenerator(
            $this->storeManager,
            $this->emulation,
            $this->seoConfig,
            $this->llmsTxtBuilder,
            $this->jsonlBuilder,
            $this->sitemapGenerator,
            $this->storeLocaleMap,
            $this->feedStorage,
            $this->logger
        );
    }

    private function activeStore(int $id = 1): Store&MockObject
    {
        $store = $this->createMock(Store::class);
        $store->method('getIsActive')->willReturn(true);
        $store->method('getId')->willReturn($id);
        $store->method('getBaseUrl')->willReturn('https://example.com/');
        return $store;
    }

    public function testInactiveStoresAreSkipped(): void
    {
        $store = $this->createMock(Store::class);
        $store->method('getIsActive')->willReturn(false);
        $this->storeManager->method('getStores')->willReturn([$store]);

        $this->emulation->expects($this->never())->method('startEnvironmentEmulation');
        $this->feedStorage->expects($this->never())->method('write');

        $this->regenerator()->regenerate();
    }

    public function testGroupFilterBuildsOnlyTheRequestedGroup(): void
    {
        $this->storeManager->method('getStores')->willReturn([$this->activeStore()]);
        $this->seoConfig->method('isLlmsTxtEnabled')->willReturn(true);
        $this->seoConfig->method('isLlmsFullTxtEnabled')->willReturn(false);
        $this->llmsTxtBuilder->method('buildConcise')->willReturn('concise');

        // Only the llms group is requested: jsonl and hreflang must not be built.
        $this->jsonlBuilder->expects($this->never())->method('build');
        $this->sitemapGenerator->expects($this->never())->method('generateFiles');
        $this->feedStorage->expects($this->once())->method('write')->with('llms.txt', 1, 'concise');

        $this->regenerator()->regenerate(FeedRegenerator::GROUP_LLMS);
    }

    public function testDisabledFeedIsNotWritten(): void
    {
        $this->storeManager->method('getStores')->willReturn([$this->activeStore()]);
        $this->seoConfig->method('isLlmsTxtEnabled')->willReturn(false);
        $this->seoConfig->method('isLlmsFullTxtEnabled')->willReturn(false);

        $this->llmsTxtBuilder->expects($this->never())->method('buildConcise');
        $this->feedStorage->expects($this->never())->method('write');

        $this->regenerator()->regenerate(FeedRegenerator::GROUP_LLMS);
    }

    public function testHreflangCleanupDeletesPerStoreNeverAllStores(): void
    {
        // Regression guard: deleting all stores' chunks inside the per-store loop
        // wiped other stores' freshly written files.
        $this->storeManager->method('getStores')->willReturn([$this->activeStore(2)]);
        $this->storeManager->method('getStore')->willReturn($this->activeStore(2));
        $this->seoConfig->method('isHreflangEnabled')->willReturn(true);
        $this->seoConfig->method('isHreflangSitemapEnabled')->willReturn(true);
        $this->storeLocaleMap->method('getMap')->willReturn([1 => ['x'], 2 => ['y']]);
        $this->sitemapGenerator->method('generateFiles')->willReturn(['hreflang-sitemap.xml' => '<xml/>']);

        $this->feedStorage->expects($this->never())->method('deleteForAllStores');
        $this->feedStorage->expects($this->once())->method('deleteForStore')
            ->with('hreflang-sitemap*.xml', 2);
        $this->feedStorage->expects($this->once())->method('write')
            ->with('hreflang-sitemap.xml', 2, '<xml/>');

        $this->regenerator()->regenerate(FeedRegenerator::GROUP_HREFLANG);
    }

    public function testHreflangSkippedWhenFewerThanTwoLocales(): void
    {
        $this->storeManager->method('getStores')->willReturn([$this->activeStore()]);
        $this->seoConfig->method('isHreflangEnabled')->willReturn(true);
        $this->seoConfig->method('isHreflangSitemapEnabled')->willReturn(true);
        $this->storeLocaleMap->method('getMap')->willReturn([1 => ['only-one']]);

        $this->sitemapGenerator->expects($this->never())->method('generateFiles');
        $this->feedStorage->expects($this->never())->method('deleteForStore');

        $this->regenerator()->regenerate(FeedRegenerator::GROUP_HREFLANG);
    }

    public function testEmulationStoppedAndLocaleMapResetInFinallyOnThrow(): void
    {
        $this->storeManager->method('getStores')->willReturn([$this->activeStore()]);
        $this->seoConfig->method('isLlmsTxtEnabled')->willReturn(true);
        $this->llmsTxtBuilder->method('buildConcise')->willThrowException(new \RuntimeException('build failed'));

        // The failure is logged, not rethrown, and the finally block always runs.
        $this->logger->expects($this->once())->method('error');
        $this->storeLocaleMap->expects($this->once())->method('reset');
        $this->emulation->expects($this->once())->method('stopEnvironmentEmulation');

        $this->regenerator()->regenerate(FeedRegenerator::GROUP_LLMS);
    }
}
