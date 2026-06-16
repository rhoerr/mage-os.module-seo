<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Config\Source;

use MageOS\Seo\Model\Config\Source\RefundType;
use PHPUnit\Framework\TestCase;

class RefundTypeTest extends TestCase
{
    public function testValuesAreSchemaOrgRefundTypeUrls(): void
    {
        $values = array_column((new RefundType())->toOptionArray(), 'value');
        $this->assertContains('https://schema.org/FullRefund', $values);
        $this->assertContains('https://schema.org/ExchangeRefund', $values);
        $this->assertContains('https://schema.org/StoreCreditRefund', $values);
    }

    public function testEachOptionHasValueAndLabel(): void
    {
        foreach ((new RefundType())->toOptionArray() as $option) {
            $this->assertArrayHasKey('value', $option);
            $this->assertNotSame('', (string) $option['label']);
        }
    }
}
