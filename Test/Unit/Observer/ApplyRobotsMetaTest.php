<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Seo\Model\RobotsMeta\Resolver;
use MageOS\Seo\Observer\ApplyRobotsMeta;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ApplyRobotsMetaTest extends TestCase
{
    /**
     * @var Resolver&MockObject
     */
    private Resolver&MockObject $resolver;

    /**
     * @var PageConfig&MockObject
     */
    private PageConfig&MockObject $pageConfig;

    /**
     * @var StoreManagerInterface&MockObject
     */
    private StoreManagerInterface&MockObject $storeManager;

    /**
     * @var ApplyRobotsMeta
     */
    private ApplyRobotsMeta $observer;

    protected function setUp(): void
    {
        $this->resolver     = $this->createMock(Resolver::class);
        $this->pageConfig   = $this->createMock(PageConfig::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);

        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(2);
        $this->storeManager->method('getStore')->willReturn($store);

        $this->observer = new ApplyRobotsMeta($this->resolver, $this->pageConfig, $this->storeManager);
    }

    public function testSetsRobotsWhenResolverReturnsValue(): void
    {
        $this->resolver->method('resolve')->with(2)->willReturn('NOINDEX,FOLLOW');
        $this->pageConfig->expects($this->once())->method('setRobots')->with('NOINDEX,FOLLOW');
        $this->observer->execute($this->createMock(Observer::class));
    }

    public function testDoesNotSetRobotsWhenResolverReturnsNull(): void
    {
        $this->resolver->method('resolve')->with(2)->willReturn(null);
        $this->pageConfig->expects($this->never())->method('setRobots');
        $this->observer->execute($this->createMock(Observer::class));
    }

    public function testDoesNotSetRobotsWhenResolverReturnsEmptyString(): void
    {
        $this->resolver->method('resolve')->with(2)->willReturn('');
        $this->pageConfig->expects($this->never())->method('setRobots');
        $this->observer->execute($this->createMock(Observer::class));
    }

    public function testSwallowsExceptionsToNeverBreakRendering(): void
    {
        $this->resolver->method('resolve')->willThrowException(new \RuntimeException('boom'));
        $this->pageConfig->expects($this->never())->method('setRobots');
        $this->observer->execute($this->createMock(Observer::class));
        $this->addToAssertionCount(1);
    }
}
