<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\RobotsMeta\Provider;

use Magento\Cms\Api\Data\PageInterface;
use MageOS\Seo\Model\Cms\CmsPageResolver;
use MageOS\Seo\Model\Config;
use MageOS\Seo\Model\RobotsMeta\Provider\CmsPageRobotsProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CmsPageRobotsProviderTest extends TestCase
{
    /**
     * @var CmsPageResolver&MockObject
     */
    private CmsPageResolver&MockObject $cmsPageResolver;

    /**
     * @var Config&MockObject
     */
    private Config&MockObject $config;

    /**
     * @var CmsPageRobotsProvider
     */
    private CmsPageRobotsProvider $provider;

    protected function setUp(): void
    {
        $this->cmsPageResolver = $this->createMock(CmsPageResolver::class);
        $this->config          = $this->createMock(Config::class);
        $this->provider        = new CmsPageRobotsProvider($this->cmsPageResolver, $this->config);
    }

    public function testHandlesCmsPageView(): void
    {
        $this->assertSame(['cms_page_view'], $this->provider->getHandles());
    }

    public function testSortOrderIsHundred(): void
    {
        $this->assertSame(100, $this->provider->getSortOrder());
    }

    public function testReturnsNullWhenNotOnCmsPage(): void
    {
        $this->cmsPageResolver->method('resolve')->willReturn(null);
        $this->assertNull($this->provider->getRobots(1));
    }

    public function testReturnsConfigDefaultWhenOnCmsPage(): void
    {
        $this->cmsPageResolver->method('resolve')->willReturn($this->createMock(PageInterface::class));
        $this->config->method('getRobotsCmsDefault')->with(1)->willReturn('INDEX,FOLLOW');
        $this->assertSame('INDEX,FOLLOW', $this->provider->getRobots(1));
    }

    public function testReturnsNullWhenConfigDefaultEmpty(): void
    {
        $this->cmsPageResolver->method('resolve')->willReturn($this->createMock(PageInterface::class));
        $this->config->method('getRobotsCmsDefault')->with(1)->willReturn('');
        $this->assertNull($this->provider->getRobots(1));
    }
}
