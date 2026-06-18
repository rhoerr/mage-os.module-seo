<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Config\Source;

use MageOS\Seo\Model\Config\Source\ReturnMethod;
use PHPUnit\Framework\TestCase;

class ReturnMethodTest extends TestCase
{
    public function testValuesAreSchemaOrgReturnMethodUrls(): void
    {
        $values = array_column((new ReturnMethod())->toOptionArray(), 'value');
        $this->assertContains('https://schema.org/ReturnByMail', $values);
        $this->assertContains('https://schema.org/ReturnInStore', $values);
        $this->assertContains('https://schema.org/ReturnAtKiosk', $values);
    }

    public function testEachOptionHasValueAndLabel(): void
    {
        foreach ((new ReturnMethod())->toOptionArray() as $option) {
            $this->assertArrayHasKey('value', $option);
            $this->assertNotSame('', (string) $option['label']);
        }
    }
}
