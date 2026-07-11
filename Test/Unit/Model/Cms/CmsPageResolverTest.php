<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Cms;

use Magento\Cms\Api\Data\PageInterface;
use MageOS\Seo\Model\Cms\CmsPageResolver;
use PHPUnit\Framework\TestCase;

/**
 * CmsPageResolver injects the code-generated Magento\Cms\Model\PageFactory,
 * which does not exist in the standalone test suite's vendor tree, so the
 * class cannot be constructed normally here. These tests build the instance
 * via reflection and cover only the memoisation and worker-mode reset
 * semantics; the resolution paths are covered by integration tests.
 */
class CmsPageResolverTest extends TestCase
{
    /**
     * Build a resolver with memoised state injected, bypassing the constructor.
     *
     * @param PageInterface|null $resolved
     * @param bool $attempted
     * @return CmsPageResolver
     */
    private function resolverWithState(?PageInterface $resolved, bool $attempted): CmsPageResolver
    {
        $reflection = new \ReflectionClass(CmsPageResolver::class);
        /** @var CmsPageResolver $resolver */
        $resolver = $reflection->newInstanceWithoutConstructor();
        $reflection->getProperty('resolved')->setValue($resolver, $resolved);
        $reflection->getProperty('attempted')->setValue($resolver, $attempted);
        return $resolver;
    }

    public function testResolveReturnsMemoisedPageWithoutTouchingDependencies(): void
    {
        // The constructor-promoted dependencies are uninitialized here, so any
        // attempt to re-resolve would throw — returning the page proves the
        // memoised path is taken.
        $page     = $this->createMock(PageInterface::class);
        $resolver = $this->resolverWithState($page, true);

        $this->assertSame($page, $resolver->resolve());
    }

    public function testResolveReturnsMemoisedNullWhenAlreadyAttempted(): void
    {
        $resolver = $this->resolverWithState(null, true);

        $this->assertNull($resolver->resolve());
    }

    public function testResetStateClearsMemoisation(): void
    {
        $page     = $this->createMock(PageInterface::class);
        $resolver = $this->resolverWithState($page, true);

        $resolver->_resetState();

        $reflection = new \ReflectionClass(CmsPageResolver::class);
        $this->assertNull($reflection->getProperty('resolved')->getValue($resolver));
        $this->assertFalse($reflection->getProperty('attempted')->getValue($resolver));
    }
}
