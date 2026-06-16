<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Hreflang\Resolver;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Framework\App\Request\Http;
use MageOS\Seo\Model\Cms\CmsPageResolver;
use MageOS\Seo\Model\Hreflang\LinkBuilder;
use MageOS\Seo\Model\Hreflang\Resolver\CmsPageHreflangResolver;
use MageOS\Seo\Model\Hreflang\StoreLocaleMap;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CmsPageHreflangResolverTest extends TestCase
{
    /**
     * @var CmsPageResolver&MockObject
     */
    private CmsPageResolver&MockObject $cmsPageResolver;

    /**
     * @var Http&MockObject
     */
    private Http&MockObject $request;

    /**
     * @var StoreLocaleMap&MockObject
     */
    private StoreLocaleMap&MockObject $storeLocaleMap;

    /**
     * @var LinkBuilder&MockObject
     */
    private LinkBuilder&MockObject $linkBuilder;

    /**
     * @var CmsPageHreflangResolver
     */
    private CmsPageHreflangResolver $resolver;

    protected function setUp(): void
    {
        $this->cmsPageResolver = $this->createMock(CmsPageResolver::class);
        $this->request         = $this->createMock(Http::class);
        $this->storeLocaleMap  = $this->createMock(StoreLocaleMap::class);
        $this->linkBuilder     = $this->createMock(LinkBuilder::class);
        $this->resolver        = new CmsPageHreflangResolver(
            $this->cmsPageResolver,
            $this->request,
            $this->storeLocaleMap,
            $this->linkBuilder
        );
    }

    public function testHandlesCmsAndHome(): void
    {
        $this->assertSame(['cms_page_view', 'cms_index_index'], $this->resolver->getHandles());
    }

    public function testHomePageUsesStoreBaseUrlsDirectly(): void
    {
        $this->request->method('getPathInfo')->willReturn('/');
        $this->storeLocaleMap->method('getMap')->willReturn([
            1 => ['base_url' => 'https://uk', 'locale' => 'en-GB', 'language' => 'en'],
            2 => ['base_url' => 'https://de', 'locale' => 'de-DE', 'language' => 'de'],
        ]);

        $links = $this->resolver->getLinks();

        $this->assertSame('https://uk/', $links[0]['url']);
        $this->assertSame('en-GB', $links[0]['hreflang']);
        $this->assertSame('https://de/', $links[1]['url']);
    }

    public function testCmsPageDelegatesToLinkBuilder(): void
    {
        $this->request->method('getPathInfo')->willReturn('/about-us');
        $page = $this->createMock(PageInterface::class);
        $page->method('getId')->willReturn(12);
        $this->cmsPageResolver->method('resolve')->willReturn($page);

        $links = [['hreflang' => 'en-GB', 'url' => 'https://uk/about-us', 'store_id' => 1]];
        $this->linkBuilder->method('build')->with('cms-page', 12)->willReturn($links);

        $this->assertSame($links, $this->resolver->getLinks());
    }

    public function testReturnsEmptyWhenCmsPageNotResolved(): void
    {
        $this->request->method('getPathInfo')->willReturn('/missing');
        $this->cmsPageResolver->method('resolve')->willReturn(null);
        $this->assertSame([], $this->resolver->getLinks());
    }
}
