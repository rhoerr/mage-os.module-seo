<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Hreflang\Resolver;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\Registry;
use MageOS\Seo\Model\Hreflang\LinkBuilder;
use MageOS\Seo\Model\Hreflang\Resolver\CategoryHreflangResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CategoryHreflangResolverTest extends TestCase
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
     * @var CategoryHreflangResolver
     */
    private CategoryHreflangResolver $resolver;

    protected function setUp(): void
    {
        $this->registry    = $this->createMock(Registry::class);
        $this->linkBuilder = $this->createMock(LinkBuilder::class);
        $this->resolver    = new CategoryHreflangResolver($this->registry, $this->linkBuilder);
    }

    public function testHandlesCategoryView(): void
    {
        $this->assertSame(['catalog_category_view'], $this->resolver->getHandles());
    }

    public function testReturnsEmptyWhenNoCurrentCategory(): void
    {
        $this->registry->method('registry')->with('current_category')->willReturn(null);
        $this->assertSame([], $this->resolver->getLinks());
    }

    public function testDelegatesToLinkBuilderWithCategoryId(): void
    {
        $category = $this->createMock(CategoryInterface::class);
        $category->method('getId')->willReturn(7);
        $this->registry->method('registry')->with('current_category')->willReturn($category);

        $links = [['hreflang' => 'de-DE', 'url' => 'https://de/c', 'store_id' => 2]];
        $this->linkBuilder->method('build')->with('category', 7)->willReturn($links);

        $this->assertSame($links, $this->resolver->getLinks());
    }
}
