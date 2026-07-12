<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Cache;

use MageOS\Seo\Model\Cache\CleaningMode;
use PHPUnit\Framework\TestCase;

class CleaningModeTest extends TestCase
{
    public function testResolvesTheMatchingAnyTagIdentifier(): void
    {
        // 'matchingAnyTag' is what cache backends expect on every supported
        // Magento version (CacheConstants on 2.4.9+, Zend_Cache before), so
        // the resolver must return it regardless of which branch it takes.
        $this->assertSame('matchingAnyTag', (new CleaningMode())->matchingAnyTag());
    }
}
