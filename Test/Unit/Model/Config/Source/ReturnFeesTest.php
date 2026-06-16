<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Config\Source;

use MageOS\Seo\Model\Config\Source\ReturnFees;
use PHPUnit\Framework\TestCase;

class ReturnFeesTest extends TestCase
{
    public function testValuesAreSchemaOrgReturnFeesUrls(): void
    {
        $values = array_column((new ReturnFees())->toOptionArray(), 'value');
        $this->assertContains('https://schema.org/FreeReturn', $values);
        $this->assertContains('https://schema.org/ReturnFeesCustomerResponsibility', $values);
        $this->assertContains('https://schema.org/ReturnShippingFees', $values);
    }

    public function testEachOptionHasValueAndLabel(): void
    {
        foreach ((new ReturnFees())->toOptionArray() as $option) {
            $this->assertArrayHasKey('value', $option);
            $this->assertNotSame('', (string) $option['label']);
        }
    }
}
