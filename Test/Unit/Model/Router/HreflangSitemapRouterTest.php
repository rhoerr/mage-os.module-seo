<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Router;

use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\Http;
use MageOS\Seo\Model\Router\HreflangSitemapRouter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HreflangSitemapRouterTest extends TestCase
{
    /**
     * @var ActionFactory&MockObject
     */
    private ActionFactory&MockObject $actionFactory;

    /**
     * @var HreflangSitemapRouter
     */
    private HreflangSitemapRouter $router;

    protected function setUp(): void
    {
        $this->actionFactory = $this->createMock(ActionFactory::class);
        $this->router        = new HreflangSitemapRouter($this->actionFactory);
    }

    private function request(string $path, string $module = ''): Http&MockObject
    {
        $request = $this->createMock(Http::class);
        $request->method('getPathInfo')->willReturn($path);
        $request->method('getModuleName')->willReturn($module);

        return $request;
    }

    public function testNonMatchingPathReturnsNull(): void
    {
        $this->actionFactory->expects($this->never())->method('create');
        $this->assertNull($this->router->match($this->request('/catalog/product/view')));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function nonMatchingProvider(): array
    {
        return [
            'missing chunk digits' => ['/hreflang-sitemap-.xml'],
            'non-numeric chunk'    => ['/hreflang-sitemap-a.xml'],
            'wrong extension'      => ['/hreflang-sitemap.html'],
            'trailing junk'        => ['/hreflang-sitemap.xml.bak'],
        ];
    }

    /**
     * @dataProvider nonMatchingProvider
     */
    public function testMalformedSitemapPathReturnsNull(string $path): void
    {
        $this->actionFactory->expects($this->never())->method('create');
        $this->assertNull($this->router->match($this->request($path)));
    }

    public function testLoopGuardReturnsNullWhenAlreadyDispatched(): void
    {
        $this->actionFactory->expects($this->never())->method('create');
        $this->assertNull($this->router->match($this->request('/hreflang-sitemap.xml', 'mageos-seo')));
    }

    public function testBaseSitemapForwardsWithChunkZero(): void
    {
        $request = $this->request('/hreflang-sitemap.xml');
        $request->method('setModuleName')->willReturnSelf();
        $request->method('setControllerName')->willReturnSelf();
        $request->method('setActionName')->willReturnSelf();
        $request->method('setAlias')->willReturnSelf();
        $request->expects($this->once())->method('setParam')->with('chunk', 0)->willReturnSelf();

        $action = $this->createMock(ActionInterface::class);
        $this->actionFactory->expects($this->once())->method('create')->willReturn($action);

        $this->assertSame($action, $this->router->match($request));
    }

    public function testChunkSitemapForwardsWithParsedChunkNumber(): void
    {
        $request = $this->request('/hreflang-sitemap-3.xml');
        $request->method('setModuleName')->willReturnSelf();
        $request->method('setControllerName')->willReturnSelf();
        $request->method('setActionName')->willReturnSelf();
        $request->method('setAlias')->willReturnSelf();
        $request->expects($this->once())->method('setParam')->with('chunk', 3)->willReturnSelf();

        $action = $this->createMock(ActionInterface::class);
        $this->actionFactory->expects($this->once())->method('create')->willReturn($action);

        $this->assertSame($action, $this->router->match($request));
    }

    public function testForwardTargetsSitemapController(): void
    {
        $request = $this->request('/hreflang-sitemap.xml');
        $request->expects($this->once())->method('setModuleName')->with('mageos-seo')->willReturnSelf();
        $request->expects($this->once())->method('setControllerName')->with('hreflangsitemap')->willReturnSelf();
        $request->expects($this->once())->method('setActionName')->with('index')->willReturnSelf();
        $request->method('setParam')->willReturnSelf();
        $request->method('setAlias')->willReturnSelf();

        $this->actionFactory->method('create')->willReturn($this->createMock(ActionInterface::class));

        $this->router->match($request);
    }
}
