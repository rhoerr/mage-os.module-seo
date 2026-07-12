<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Framework\View\Page\Title;
use MageOS\Seo\Model\PageTitle\Compositor;
use MageOS\Seo\Observer\ApplyPageTitle;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ApplyPageTitleTest extends TestCase
{
    /**
     * @var Compositor&MockObject
     */
    private Compositor&MockObject $compositor;

    /**
     * @var PageConfig&MockObject
     */
    private PageConfig&MockObject $pageConfig;

    /**
     * @var ApplyPageTitle
     */
    private ApplyPageTitle $observer;

    protected function setUp(): void
    {
        $this->compositor = $this->createMock(Compositor::class);
        $this->pageConfig = $this->createMock(PageConfig::class);
        $this->observer   = new ApplyPageTitle($this->compositor, $this->pageConfig);
    }

    public function testSetsTitleWhenCompositorReturnsNonEmpty(): void
    {
        $this->compositor->method('getTitle')->willReturn('Winning Title');

        $title = $this->createMock(Title::class);
        $title->expects($this->once())->method('set')->with('Winning Title');
        $this->pageConfig->method('getTitle')->willReturn($title);

        $this->observer->execute($this->createMock(Observer::class));
    }

    public function testDoesNothingWhenCompositorReturnsEmpty(): void
    {
        // Core behaviour must be untouched when no provider supplied a title.
        $this->compositor->method('getTitle')->willReturn('');
        $this->pageConfig->expects($this->never())->method('getTitle');

        $this->observer->execute($this->createMock(Observer::class));
    }

    public function testSwallowsExceptionsSoRenderingNeverBreaks(): void
    {
        $this->compositor->method('getTitle')->willThrowException(new \RuntimeException('boom'));
        // getTitle() is never reached, and the exception must not propagate out of execute().
        $this->pageConfig->expects($this->never())->method('getTitle');

        $this->observer->execute($this->createMock(Observer::class));
    }
}
