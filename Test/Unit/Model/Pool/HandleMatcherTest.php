<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Pool;

use MageOS\Seo\Model\Pool\HandleMatcher;
use PHPUnit\Framework\TestCase;

class HandleMatcherTest extends TestCase
{
    /**
     * @var HandleMatcher
     */
    private HandleMatcher $matcher;

    protected function setUp(): void
    {
        $this->matcher = new HandleMatcher();
    }

    public function testWildcardMatchesAnyActiveHandles(): void
    {
        $this->assertTrue($this->matcher->matches(['*'], ['cms_page_view']));
    }

    public function testWildcardMatchesEvenWithNoActiveHandles(): void
    {
        $this->assertTrue($this->matcher->matches(['*'], []));
    }

    public function testExactHandleMatch(): void
    {
        $this->assertTrue(
            $this->matcher->matches(['catalog_product_view'], ['default', 'catalog_product_view'])
        );
    }

    public function testPartialOverlapMatches(): void
    {
        $this->assertTrue(
            $this->matcher->matches(
                ['catalog_product_view', 'catalog_category_view'],
                ['default', 'catalog_category_view', 'catalog_category_view_id_3']
            )
        );
    }

    public function testNoOverlapDoesNotMatch(): void
    {
        $this->assertFalse(
            $this->matcher->matches(['catalog_product_view'], ['default', 'cms_index_index'])
        );
    }

    public function testEmptyProviderHandlesDoNotMatch(): void
    {
        $this->assertFalse($this->matcher->matches([], ['catalog_product_view']));
    }

    public function testEmptyActiveHandlesDoNotMatchSpecificHandle(): void
    {
        $this->assertFalse($this->matcher->matches(['catalog_product_view'], []));
    }
}
