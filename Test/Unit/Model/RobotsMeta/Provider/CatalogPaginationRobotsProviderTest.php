<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\RobotsMeta\Provider;

use Magento\Framework\App\RequestInterface;
use MageOS\Seo\Model\Config;
use MageOS\Seo\Model\RobotsMeta\Provider\CatalogPaginationRobotsProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CatalogPaginationRobotsProviderTest extends TestCase
{
    /**
     * @var RequestInterface&MockObject
     */
    private RequestInterface&MockObject $request;

    /**
     * @var Config&MockObject
     */
    private Config&MockObject $config;

    /**
     * @var CatalogPaginationRobotsProvider
     */
    private CatalogPaginationRobotsProvider $provider;

    protected function setUp(): void
    {
        $this->request  = $this->createMock(RequestInterface::class);
        $this->config   = $this->createMock(Config::class);
        $this->provider = new CatalogPaginationRobotsProvider($this->request, $this->config);
    }

    public function testHandlesCategoryView(): void
    {
        $this->assertSame(['catalog_category_view'], $this->provider->getHandles());
    }

    public function testSortOrderIsAboveCategoryDefault(): void
    {
        $this->assertSame(200, $this->provider->getSortOrder());
    }

    public function testReturnsNullWhenDisabled(): void
    {
        $this->config->method('isPaginatedRobotsEnabled')->with(1)->willReturn(false);
        $this->assertNull($this->provider->getRobots(1));
    }

    public function testReturnsNullOnFirstPage(): void
    {
        $this->config->method('isPaginatedRobotsEnabled')->with(1)->willReturn(true);
        $this->request->method('getParam')->with('p')->willReturn('1');
        $this->assertNull($this->provider->getRobots(1));
    }

    public function testReturnsNullWhenNoPageParam(): void
    {
        $this->config->method('isPaginatedRobotsEnabled')->with(1)->willReturn(true);
        $this->request->method('getParam')->with('p')->willReturn(null);
        $this->assertNull($this->provider->getRobots(1));
    }

    public function testReturnsConfiguredRobotsOnSecondPage(): void
    {
        $this->config->method('isPaginatedRobotsEnabled')->with(1)->willReturn(true);
        $this->request->method('getParam')->with('p')->willReturn('2');
        $this->config->method('getRobotsPaginated')->with(1)->willReturn('NOINDEX,FOLLOW');
        $this->assertSame('NOINDEX,FOLLOW', $this->provider->getRobots(1));
    }

    public function testReturnsNullWhenEnabledButRobotsValueEmpty(): void
    {
        $this->config->method('isPaginatedRobotsEnabled')->with(1)->willReturn(true);
        $this->request->method('getParam')->with('p')->willReturn('3');
        $this->config->method('getRobotsPaginated')->with(1)->willReturn('');
        $this->assertNull($this->provider->getRobots(1));
    }
}
