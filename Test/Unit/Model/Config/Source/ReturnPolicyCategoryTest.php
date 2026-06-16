<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Config\Source;

use MageOS\Seo\Model\Config\Source\ReturnPolicyCategory;
use PHPUnit\Framework\TestCase;

class ReturnPolicyCategoryTest extends TestCase
{
    public function testValuesAreSchemaOrgReturnCategoryUrls(): void
    {
        $values = array_column((new ReturnPolicyCategory())->toOptionArray(), 'value');
        $this->assertContains('https://schema.org/MerchantReturnFiniteReturnWindow', $values);
        $this->assertContains('https://schema.org/MerchantReturnUnlimitedWindow', $values);
        $this->assertContains('https://schema.org/MerchantReturnNotPermitted', $values);
    }

    public function testEachOptionHasValueAndLabel(): void
    {
        foreach ((new ReturnPolicyCategory())->toOptionArray() as $option) {
            $this->assertArrayHasKey('value', $option);
            $this->assertNotSame('', (string) $option['label']);
        }
    }
}
