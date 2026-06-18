<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Config\Source;

use MageOS\Seo\Model\Config\Source\ItemCondition;
use PHPUnit\Framework\TestCase;

class ItemConditionTest extends TestCase
{
    public function testValuesAreSchemaOrgConditionUrls(): void
    {
        $values = array_column((new ItemCondition())->toOptionArray(), 'value');
        $this->assertContains('https://schema.org/NewCondition', $values);
        $this->assertContains('https://schema.org/RefurbishedCondition', $values);
        $this->assertContains('https://schema.org/UsedCondition', $values);
        $this->assertContains('https://schema.org/DamagedCondition', $values);
    }

    public function testEachOptionHasValueAndLabel(): void
    {
        foreach ((new ItemCondition())->toOptionArray() as $option) {
            $this->assertArrayHasKey('value', $option);
            $this->assertNotSame('', (string) $option['label']);
        }
    }
}
