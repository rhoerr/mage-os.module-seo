<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Canonical;

use Magento\Framework\View\Asset\AssetInterface;
use Magento\Framework\View\Asset\GroupedCollection;
use Magento\Framework\View\Page\Config as PageConfig;
use MageOS\Seo\Model\Canonical\CanonicalUrlManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CanonicalUrlManagerTest extends TestCase
{
    /**
     * @var PageConfig&MockObject
     */
    private PageConfig&MockObject $pageConfig;

    /**
     * @var GroupedCollection&MockObject
     */
    private GroupedCollection&MockObject $assetCollection;

    /**
     * @var CanonicalUrlManager
     */
    private CanonicalUrlManager $manager;

    protected function setUp(): void
    {
        $this->pageConfig      = $this->createMock(PageConfig::class);
        $this->assetCollection = $this->createMock(GroupedCollection::class);
        $this->pageConfig->method('getAssetCollection')->willReturn($this->assetCollection);
        $this->manager = new CanonicalUrlManager();
    }

    /**
     * Build an asset mock reporting the given content type.
     */
    private function makeAsset(string $contentType): AssetInterface&MockObject
    {
        $asset = $this->createMock(AssetInterface::class);
        $asset->method('getContentType')->willReturn($contentType);
        return $asset;
    }

    public function testSetCanonicalCallsAddRemotePageAsset(): void
    {
        $this->assetCollection->method('getAll')->willReturn([]);
        $this->pageConfig
            ->expects($this->once())
            ->method('addRemotePageAsset')
            ->with(
                'https://example.com/my-product',
                'canonical',
                ['attributes' => ['rel' => 'canonical']]
            );

        $this->manager->setCanonical('https://example.com/my-product', $this->pageConfig);
    }

    public function testSetCanonicalRemovesExistingCanonicalAssets(): void
    {
        $this->assetCollection->method('getAll')->willReturn([
            'https://example.com/my-product' => $this->makeAsset('canonical'),
            'css/styles.css'                 => $this->makeAsset('css'),
        ]);
        $this->assetCollection
            ->expects($this->once())
            ->method('remove')
            ->with('https://example.com/my-product');

        $this->pageConfig->method('addRemotePageAsset')->willReturnSelf();

        $this->manager->setCanonical('https://example.com/my-product?variant=red', $this->pageConfig);
    }

    public function testRemovalIgnoresNonCanonicalAssetsWhoseIdentifierMatchesUrlKey(): void
    {
        // Regression: identifier-pattern matching removed css/print.css for a product
        // with url_key "print" — only content type "canonical" may be removed.
        $this->assetCollection->method('getAll')->willReturn([
            'css/print.css' => $this->makeAsset('css'),
            'js/print.js'   => $this->makeAsset('js'),
        ]);
        $this->assetCollection->expects($this->never())->method('remove');
        $this->pageConfig->method('addRemotePageAsset')->willReturnSelf();

        $this->manager->setCanonical('https://example.com/print', $this->pageConfig, 'print');
    }

    public function testSetCanonicalAlwaysAddsNewCanonicalEvenAfterRemoval(): void
    {
        $this->assetCollection->method('getAll')->willReturn([
            'https://example.com/product.html' => $this->makeAsset('canonical'),
        ]);
        $this->assetCollection->method('remove');

        $this->pageConfig
            ->expects($this->once())
            ->method('addRemotePageAsset')
            ->with('https://example.com/product?variant=blue', 'canonical', $this->anything());

        $this->manager->setCanonical('https://example.com/product?variant=blue', $this->pageConfig, 'product');
    }
}
