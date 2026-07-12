<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Block;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Framework\View\Asset\AssetInterface;
use Magento\Framework\View\Asset\GroupedCollection;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Layout\ProcessorInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Seo\Block\Canonical;
use MageOS\Seo\Model\Cms\CmsPageResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CanonicalTest extends TestCase
{
    /**
     * @var ProcessorInterface&MockObject
     */
    private ProcessorInterface&MockObject $layoutProcessor;

    /**
     * @var GroupedCollection&MockObject
     */
    private GroupedCollection&MockObject $assetCollection;

    /**
     * @var CmsPageResolver&MockObject
     */
    private CmsPageResolver&MockObject $cmsPageResolver;

    /**
     * @var Canonical
     */
    private Canonical $block;

    protected function setUp(): void
    {
        $this->layoutProcessor = $this->createMock(ProcessorInterface::class);
        $layout                = $this->createMock(LayoutInterface::class);
        $layout->method('getUpdate')->willReturn($this->layoutProcessor);

        $this->assetCollection = $this->createMock(GroupedCollection::class);
        // Default: no existing canonical asset on the page.
        $this->assetCollection->method('getAll')->willReturn([]);
        $pageConfig = $this->createMock(PageConfig::class);
        $pageConfig->method('getAssetCollection')->willReturn($this->assetCollection);

        $context = $this->createMock(Context::class);
        $context->method('getPageConfig')->willReturn($pageConfig);

        $this->cmsPageResolver = $this->createMock(CmsPageResolver::class);

        $store = $this->createMock(Store::class);
        $store->method('getBaseUrl')->willReturn('https://example.com/');
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $this->block = new Canonical($context, $this->cmsPageResolver, $storeManager);
        $this->block->setLayout($layout);
    }

    private function withHandles(string ...$handles): void
    {
        $this->layoutProcessor->method('getHandles')->willReturn($handles);
    }

    public function testHomePageReturnsBaseUrl(): void
    {
        $this->withHandles('cms_index_index');
        $this->assertSame('https://example.com/', $this->block->getCanonicalUrl());
    }

    public function testCmsPageReturnsBaseUrlPlusIdentifier(): void
    {
        $this->withHandles('cms_page_view');
        $page = $this->createMock(PageInterface::class);
        $page->method('getIdentifier')->willReturn('about-us');
        $this->cmsPageResolver->method('resolve')->willReturn($page);

        $this->assertSame('https://example.com/about-us', $this->block->getCanonicalUrl());
    }

    public function testCmsPageWithoutResolvedPageReturnsEmpty(): void
    {
        $this->withHandles('cms_page_view');
        $this->cmsPageResolver->method('resolve')->willReturn(null);

        $this->assertSame('', $this->block->getCanonicalUrl());
    }

    public function testProductPageReturnsEmpty(): void
    {
        // Product/category canonicals are core's job; this block stays off them.
        $this->withHandles('catalog_product_view');
        $this->assertSame('', $this->block->getCanonicalUrl());
    }

    public function testNonIndexablePageReturnsEmpty(): void
    {
        $this->withHandles('checkout_cart_index');
        $this->assertSame('', $this->block->getCanonicalUrl());
    }

    public function testExistingCanonicalAssetShortCircuitsToEmpty(): void
    {
        // A canonical already added by core/another module must not be duplicated,
        // even on the home page.
        $asset = $this->createMock(AssetInterface::class);
        $asset->method('getContentType')->willReturn('canonical');

        // Rebuild the block with an asset collection that already holds a canonical.
        $collection = $this->createMock(GroupedCollection::class);
        $collection->method('getAll')->willReturn([$asset]);
        $pageConfig = $this->createMock(PageConfig::class);
        $pageConfig->method('getAssetCollection')->willReturn($collection);
        $context = $this->createMock(Context::class);
        $context->method('getPageConfig')->willReturn($pageConfig);

        $layout = $this->createMock(LayoutInterface::class);
        $processor = $this->createMock(ProcessorInterface::class);
        $processor->method('getHandles')->willReturn(['cms_index_index']);
        $layout->method('getUpdate')->willReturn($processor);

        $store = $this->createMock(Store::class);
        $store->method('getBaseUrl')->willReturn('https://example.com/');
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $block = new Canonical($context, $this->createMock(CmsPageResolver::class), $storeManager);
        $block->setLayout($layout);

        $this->assertSame('', $block->getCanonicalUrl());
    }
}
