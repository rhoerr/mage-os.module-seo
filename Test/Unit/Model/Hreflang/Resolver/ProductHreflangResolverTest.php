<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Hreflang\Resolver;

use Magento\Catalog\Api\Data\ProductInterface;
use MageOS\Seo\Model\Catalog\CurrentEntity;
use MageOS\Seo\Model\Hreflang\LinkBuilder;
use MageOS\Seo\Model\Hreflang\Resolver\ProductHreflangResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductHreflangResolverTest extends TestCase
{
    /**
     * @var CurrentEntity&MockObject
     */
    private CurrentEntity&MockObject $currentEntity;

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
        $this->currentEntity    = $this->createMock(CurrentEntity::class);
        $this->linkBuilder = $this->createMock(LinkBuilder::class);
        $this->resolver    = new ProductHreflangResolver($this->currentEntity, $this->linkBuilder);
    }

    public function testHandlesProductView(): void
    {
        $this->assertSame(['catalog_product_view'], $this->resolver->getHandles());
    }

    public function testReturnsEmptyWhenNoCurrentProduct(): void
    {
        $this->currentEntity->method('getProduct')->willReturn(null);
        $this->assertSame([], $this->resolver->getLinks());
    }

    public function testDelegatesToLinkBuilderWithProductId(): void
    {
        $product = $this->createMock(ProductInterface::class);
        $product->method('getId')->willReturn(42);
        $this->currentEntity->method('getProduct')->willReturn($product);

        $links = [['hreflang' => 'en-GB', 'url' => 'https://uk/p', 'store_id' => 1]];
        $this->linkBuilder->method('build')->with('product', 42)->willReturn($links);

        $this->assertSame($links, $this->resolver->getLinks());
    }
}
