<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Hreflang\Resolver;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Registry;
use MageOS\Seo\Model\Hreflang\LinkBuilder;
use MageOS\Seo\Model\Hreflang\Resolver\ProductHreflangResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductHreflangResolverTest extends TestCase
{
    /**
     * @var Registry&MockObject
     */
    private Registry&MockObject $registry;

    /**
     * @var LinkBuilder&MockObject
     */
    private LinkBuilder&MockObject $linkBuilder;

    /**
     * @var ProductHreflangResolver
     */
    private ProductHreflangResolver $resolver;

    protected function setUp(): void
    {
        $this->registry    = $this->createMock(Registry::class);
        $this->linkBuilder = $this->createMock(LinkBuilder::class);
        $this->resolver    = new ProductHreflangResolver($this->registry, $this->linkBuilder);
    }

    public function testHandlesProductView(): void
    {
        $this->assertSame(['catalog_product_view'], $this->resolver->getHandles());
    }

    public function testReturnsEmptyWhenNoCurrentProduct(): void
    {
        $this->registry->method('registry')->with('current_product')->willReturn(null);
        $this->assertSame([], $this->resolver->getLinks());
    }

    public function testDelegatesToLinkBuilderWithProductId(): void
    {
        $product = $this->createMock(ProductInterface::class);
        $product->method('getId')->willReturn(42);
        $this->registry->method('registry')->with('current_product')->willReturn($product);

        $links = [['hreflang' => 'en-GB', 'url' => 'https://uk/p', 'store_id' => 1]];
        $this->linkBuilder->method('build')->with('product', 42)->willReturn($links);

        $this->assertSame($links, $this->resolver->getLinks());
    }
}
