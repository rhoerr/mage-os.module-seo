<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Product;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Seo\Model\Product\AvailabilityResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AvailabilityResolverTest extends TestCase
{
    /**
     * @var StoreManagerInterface&MockObject
     */
    private StoreManagerInterface&MockObject $storeManager;

    /**
     * @var StockResolverInterface&MockObject
     */
    private StockResolverInterface&MockObject $stockResolver;

    /**
     * @var IsProductSalableInterface&MockObject
     */
    private IsProductSalableInterface&MockObject $isProductSalable;

    /**
     * @var GetStockItemConfigurationInterface&MockObject
     */
    private GetStockItemConfigurationInterface&MockObject $getStockItemConfiguration;

    /**
     * @var AvailabilityResolver
     */
    private AvailabilityResolver $resolver;

    protected function setUp(): void
    {
        $this->storeManager              = $this->createMock(StoreManagerInterface::class);
        $this->stockResolver             = $this->createMock(StockResolverInterface::class);
        $this->isProductSalable          = $this->createMock(IsProductSalableInterface::class);
        $this->getStockItemConfiguration = $this->createMock(GetStockItemConfigurationInterface::class);

        $website = $this->createMock(WebsiteInterface::class);
        $website->method('getCode')->willReturn('base');
        $this->storeManager->method('getWebsite')->willReturn($website);

        $this->resolver = new AvailabilityResolver(
            $this->storeManager,
            $this->stockResolver,
            $this->isProductSalable,
            $this->getStockItemConfiguration
        );
    }

    private function makeProduct(string $sku): Product&MockObject
    {
        $product = $this->createMock(Product::class);
        $product->method('getSku')->willReturn($sku);
        return $product;
    }

    private function stubStockId(int $stockId): void
    {
        $stock = $this->createMock(StockInterface::class);
        $stock->method('getStockId')->willReturn($stockId);
        $this->stockResolver->method('execute')->willReturn($stock);
    }

    public function testEmptySkuResolvesOutOfStockWithoutTouchingInventory(): void
    {
        $this->stockResolver->expects($this->never())->method('execute');

        $this->assertSame(
            AvailabilityResolver::OUT_OF_STOCK,
            $this->resolver->resolve($this->makeProduct(''))
        );
    }

    public function testSalableProductResolvesInStock(): void
    {
        $this->stubStockId(1);
        $this->isProductSalable->method('execute')->with('SKU-1', 1)->willReturn(true);

        $this->assertSame(
            AvailabilityResolver::IN_STOCK,
            $this->resolver->resolve($this->makeProduct('SKU-1'))
        );
    }

    public function testBackorderableOutOfStockResolvesBackOrder(): void
    {
        $this->stubStockId(1);
        $this->isProductSalable->method('execute')->willReturn(false);

        $configuration = $this->createMock(StockItemConfigurationInterface::class);
        $configuration->method('getBackorders')->willReturn(1);
        $this->getStockItemConfiguration->method('execute')->with('SKU-1', 1)->willReturn($configuration);

        $this->assertSame(
            AvailabilityResolver::BACKORDER,
            $this->resolver->resolve($this->makeProduct('SKU-1'))
        );
    }

    public function testNonBackorderableOutOfStockResolvesOutOfStock(): void
    {
        $this->stubStockId(1);
        $this->isProductSalable->method('execute')->willReturn(false);

        $configuration = $this->createMock(StockItemConfigurationInterface::class);
        $configuration->method('getBackorders')->willReturn(0);
        $this->getStockItemConfiguration->method('execute')->willReturn($configuration);

        $this->assertSame(
            AvailabilityResolver::OUT_OF_STOCK,
            $this->resolver->resolve($this->makeProduct('SKU-1'))
        );
    }

    public function testInventoryExceptionResolvesOutOfStock(): void
    {
        // Product not assigned to any stock/source: the MSI contracts throw
        // rather than answering, and the resolver must degrade to OutOfStock.
        $this->stubStockId(1);
        $this->isProductSalable->method('execute')
            ->willThrowException(new NoSuchEntityException(__('not assigned')));

        $this->assertSame(
            AvailabilityResolver::OUT_OF_STOCK,
            $this->resolver->resolve($this->makeProduct('SKU-1'))
        );
    }

    public function testStockIdIsMemoisedPerWebsite(): void
    {
        $stock = $this->createMock(StockInterface::class);
        $stock->method('getStockId')->willReturn(1);
        $this->stockResolver->expects($this->once())->method('execute')->willReturn($stock);
        $this->isProductSalable->method('execute')->willReturn(true);

        $this->resolver->resolve($this->makeProduct('SKU-1'));
        $this->resolver->resolve($this->makeProduct('SKU-2'));
    }

    public function testResetStateDropsTheMemoisedStockIds(): void
    {
        $stock = $this->createMock(StockInterface::class);
        $stock->method('getStockId')->willReturn(1);
        $this->stockResolver->expects($this->exactly(2))->method('execute')->willReturn($stock);
        $this->isProductSalable->method('execute')->willReturn(true);

        $this->resolver->resolve($this->makeProduct('SKU-1'));
        $this->resolver->_resetState();
        $this->resolver->resolve($this->makeProduct('SKU-1'));
    }
}
