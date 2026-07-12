<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Hreflang\Resolver;

use Magento\Catalog\Api\Data\CategoryInterface;
use MageOS\Seo\Model\Catalog\CurrentEntity;
use MageOS\Seo\Model\Hreflang\LinkBuilder;
use MageOS\Seo\Model\Hreflang\Resolver\CategoryHreflangResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CategoryHreflangResolverTest extends TestCase
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
     * @var CategoryHreflangResolver
     */
    private CategoryHreflangResolver $resolver;

    protected function setUp(): void
    {
        $this->currentEntity    = $this->createMock(CurrentEntity::class);
        $this->linkBuilder = $this->createMock(LinkBuilder::class);
        $this->resolver    = new CategoryHreflangResolver($this->currentEntity, $this->linkBuilder);
    }

    public function testHandlesCategoryView(): void
    {
        $this->assertSame(['catalog_category_view'], $this->resolver->getHandles());
    }

    public function testReturnsEmptyWhenNoCurrentCategory(): void
    {
        $this->currentEntity->method('getCategory')->willReturn(null);
        $this->assertSame([], $this->resolver->getLinks());
    }

    public function testDelegatesToLinkBuilderWithCategoryId(): void
    {
        $category = $this->createMock(CategoryInterface::class);
        $category->method('getId')->willReturn(7);
        $this->currentEntity->method('getCategory')->willReturn($category);

        $links = [['hreflang' => 'de-DE', 'url' => 'https://de/c', 'store_id' => 2]];
        $this->linkBuilder->method('build')->with('category', 7)->willReturn($links);

        $this->assertSame($links, $this->resolver->getLinks());
    }
}
