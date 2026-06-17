<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Ucp;

use MageOS\Seo\Model\Ucp\SecurityTxtBuilder;
use MageOS\Seo\Model\Ucp\UcpConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SecurityTxtBuilderTest extends TestCase
{
    private UcpConfig&MockObject $config;
    private SecurityTxtBuilder $builder;

    protected function setUp(): void
    {
        $this->config = $this->createMock(UcpConfig::class);
        $this->builder = new SecurityTxtBuilder($this->config);
    }

    public function testBareEmailIsNormalisedToMailto(): void
    {
        $this->config->method('getSecurityContactEmail')->willReturn('security@shop.test');
        $this->config->method('getSecurityExpires')->willReturn('2027-01-01T00:00:00.000Z');
        $this->config->method('getSecurityPolicyUrl')->willReturn('https://shop.test/security');

        $output = $this->builder->build();

        $this->assertStringContainsString('Contact: mailto:security@shop.test', $output);
        $this->assertStringContainsString('Expires: 2027-01-01T00:00:00.000Z', $output);
        $this->assertStringContainsString('Preferred-Languages: en', $output);
        $this->assertStringContainsString('Policy: https://shop.test/security', $output);
        $this->assertStringEndsWith("\n", $output);
    }

    public function testAlreadyQualifiedContactUriIsKept(): void
    {
        $this->config->method('getSecurityContactEmail')->willReturn('https://shop.test/contact');
        $this->config->method('getSecurityExpires')->willReturn('');
        $this->config->method('getSecurityPolicyUrl')->willReturn('');

        $output = $this->builder->build();

        $this->assertStringContainsString('Contact: https://shop.test/contact', $output);
        $this->assertStringNotContainsString('mailto:', $output);
    }

    public function testOptionalFieldsOmittedWhenBlank(): void
    {
        $this->config->method('getSecurityContactEmail')->willReturn('');
        $this->config->method('getSecurityExpires')->willReturn('');
        $this->config->method('getSecurityPolicyUrl')->willReturn('');

        $output = $this->builder->build();

        $this->assertStringNotContainsString('Contact:', $output);
        $this->assertStringNotContainsString('Expires:', $output);
        $this->assertStringNotContainsString('Policy:', $output);
        // Preferred-Languages is always present.
        $this->assertStringContainsString('Preferred-Languages: en', $output);
    }
}
