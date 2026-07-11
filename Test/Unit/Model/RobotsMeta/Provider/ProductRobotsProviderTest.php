<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\RobotsMeta\Provider;

use Magento\Catalog\Api\Data\ProductInterface;
use MageOS\Seo\Model\Catalog\CurrentEntity;
use MageOS\Seo\Model\Category\ProductOverrideRepository;
use MageOS\Seo\Model\Config;
use MageOS\Seo\Model\RobotsMeta\Provider\ProductRobotsProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductRobotsProviderTest extends TestCase
{
    /**
     * @var CurrentEntity&MockObject
     */
    private CurrentEntity&MockObject $currentEntity;

    /**
     * @var ProductOverrideRepository&MockObject
     */
    private ProductOverrideRepository&MockObject $overrideRepository;

    /**
     * @var Config&MockObject
     */
    private Config&MockObject $config;

    /**
     * @var ProductRobotsProvider
     */
    private ProductRobotsProvider $provider;

    protected function setUp(): void
    {
        $this->currentEntity           = $this->createMock(CurrentEntity::class);
        $this->overrideRepository = $this->createMock(ProductOverrideRepository::class);
        $this->config             = $this->createMock(Config::class);
        $this->provider           = new ProductRobotsProvider(
            $this->currentEntity,
            $this->overrideRepository,
            $this->config
        );
    }

    private function withProduct(int $id): void
    {
        $product = $this->createMock(ProductInterface::class);
        $product->method('getId')->willReturn($id);
        $this->currentEntity->method('getProduct')->willReturn($product);
    }

    public function testHandlesProductView(): void
    {
        $this->assertSame(['catalog_product_view'], $this->provider->getHandles());
    }

    public function testSortOrderIsHundred(): void
    {
        $this->assertSame(100, $this->provider->getSortOrder());
    }

    public function testReturnsNullWhenNoCurrentProduct(): void
    {
        $this->currentEntity->method('getProduct')->willReturn(null);
        $this->assertNull($this->provider->getRobots(1));
    }

    public function testReturnsPerProductOverride(): void
    {
        $this->withProduct(5);
        $this->overrideRepository->method('getForProduct')->with(5, 1)
            ->willReturn(['override_fields' => [], 'robots_meta' => 'NOINDEX,FOLLOW']);
        $this->assertSame('NOINDEX,FOLLOW', $this->provider->getRobots(1));
    }

    public function testFallsBackToConfigDefaultWhenNoOverride(): void
    {
        $this->withProduct(5);
        $this->overrideRepository->method('getForProduct')->with(5, 1)
            ->willReturn(['override_fields' => [], 'robots_meta' => null]);
        $this->config->method('getRobotsProductDefault')->with(1)->willReturn('INDEX,FOLLOW');
        $this->assertSame('INDEX,FOLLOW', $this->provider->getRobots(1));
    }

    public function testReturnsNullWhenOverrideAndDefaultBothEmpty(): void
    {
        $this->withProduct(5);
        $this->overrideRepository->method('getForProduct')->with(5, 1)
            ->willReturn(['override_fields' => [], 'robots_meta' => null]);
        $this->config->method('getRobotsProductDefault')->with(1)->willReturn('');
        $this->assertNull($this->provider->getRobots(1));
    }
}
