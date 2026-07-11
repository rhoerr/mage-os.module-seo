<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\LlmsJsonl;

use Magento\Catalog\Model\Product;
use Magento\Framework\Pricing\Price\PriceInterface;
use Magento\Framework\Pricing\PriceInfoInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Seo\Model\LlmsJsonl\ProductLineBuilder;
use MageOS\Seo\Service\CurrencyService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductLineBuilderTest extends TestCase
{
    /**
     * @var Store&MockObject
     */
    private Store&MockObject $store;

    /**
     * @var Product&MockObject
     */
    private Product&MockObject $product;

    /**
     * @var ProductLineBuilder
     */
    private ProductLineBuilder $builder;

    protected function setUp(): void
    {
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $this->store  = $this->createMock(Store::class);
        $storeManager->method('getStore')->willReturn($this->store);

        $currency = $this->createMock(CurrencyService::class);
        $currency->method('getCurrentCurrencyCode')->willReturn('GBP');
        $currency->method('convertFromBase')->willReturnArgument(0);

        $finalPrice = $this->createMock(PriceInterface::class);
        $finalPrice->method('getValue')->willReturn(29.99);
        $priceInfo = $this->createMock(PriceInfoInterface::class);
        $priceInfo->method('getPrice')->with('final_price')->willReturn($finalPrice);

        $this->product = $this->createMock(Product::class);
        $this->product->method('getPriceInfo')->willReturn($priceInfo);
        $this->product->method('getName')->willReturn('Blue Mug');
        $this->product->method('getSku')->willReturn('MUG-001');
        $this->product->method('getProductUrl')->willReturn('https://example.com/blue-mug.html');
        $this->product->method('isSalable')->willReturn(true);

        $this->builder = new ProductLineBuilder($storeManager, $currency);
    }

    public function testBuildsRequiredFields(): void
    {
        $node = $this->builder->build($this->product);

        $this->assertSame('https://schema.org', $node['@context']);
        $this->assertSame('Product', $node['@type']);
        $this->assertSame('https://example.com/blue-mug.html', $node['@id']);
        $this->assertSame('Blue Mug', $node['name']);
        $this->assertSame('MUG-001', $node['sku']);
        $this->assertSame('Offer', $node['offers']['@type']);
        $this->assertSame('29.99', $node['offers']['price']);
        $this->assertSame('GBP', $node['offers']['priceCurrency']);
        $this->assertSame('https://schema.org/InStock', $node['offers']['availability']);
    }

    public function testOutOfStockAvailability(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getName')->willReturn('X');
        $product->method('getProductUrl')->willReturn('https://example.com/x');
        $product->method('getPriceInfo')->willReturn($this->product->getPriceInfo());
        $product->method('isSalable')->willReturn(false);

        $node = $this->builder->build($product);
        $this->assertSame('https://schema.org/OutOfStock', $node['offers']['availability']);
    }

    public function testDescriptionStrippedAndTruncated(): void
    {
        $this->product->method('__call')->willReturnCallback(
            fn (string $m) => $m === 'getShortDescription' ? '<p>A <strong>great</strong> mug</p>' : null
        );
        $node = $this->builder->build($this->product);
        $this->assertSame('A great mug', $node['description']);
    }

    public function testImageBuiltFromMediaUrl(): void
    {
        $this->product->method('getImage')->willReturn('/m/u/mug.jpg');
        $this->store->method('getBaseUrl')->with(UrlInterface::URL_TYPE_MEDIA)
            ->willReturn('https://example.com/media/');

        $node = $this->builder->build($this->product);
        $this->assertSame('https://example.com/media/catalog/product/m/u/mug.jpg', $node['image']);
    }

    public function testImageOmittedWhenNoSelection(): void
    {
        $this->product->method('getImage')->willReturn('no_selection');
        $node = $this->builder->build($this->product);
        $this->assertArrayNotHasKey('image', $node);
    }
}
