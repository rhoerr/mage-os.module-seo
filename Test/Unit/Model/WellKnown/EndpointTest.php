<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\WellKnown;

use MageOS\Seo\Model\Ucp\AiPluginBuilder;
use MageOS\Seo\Model\Ucp\ProfileBuilder;
use MageOS\Seo\Model\Ucp\SecurityTxtBuilder;
use MageOS\Seo\Model\Ucp\UcpConfig;
use MageOS\Seo\Model\WellKnown\Endpoint\AiPluginEndpoint;
use MageOS\Seo\Model\WellKnown\Endpoint\SecurityTxtEndpoint;
use MageOS\Seo\Model\WellKnown\Endpoint\UcpEndpoint;
use PHPUnit\Framework\TestCase;

class EndpointTest extends TestCase
{
    public function testUcpEndpoint(): void
    {
        $config = $this->createMock(UcpConfig::class);
        $config->method('isUcpEnabled')->willReturn(true);
        $profile = $this->createMock(ProfileBuilder::class);
        $profile->method('build')->willReturn(['version' => '2026-04-08']);

        $endpoint = new UcpEndpoint($config, $profile);

        $this->assertSame('ucp', $endpoint->getName());
        $this->assertTrue($endpoint->isEnabled());
        $this->assertStringContainsString('application/json', $endpoint->getContentType());
        $this->assertStringContainsString('max-age=300', $endpoint->getCacheControl());
        $this->assertJsonStringEqualsJsonString('{"version":"2026-04-08"}', $endpoint->render());
    }

    public function testAiPluginEndpoint(): void
    {
        $config = $this->createMock(UcpConfig::class);
        $config->method('isAiPluginEnabled')->willReturn(false);
        $builder = $this->createMock(AiPluginBuilder::class);
        $builder->method('build')->willReturn(['schema_version' => 'v1']);

        $endpoint = new AiPluginEndpoint($config, $builder);

        $this->assertSame('ai-plugin.json', $endpoint->getName());
        $this->assertFalse($endpoint->isEnabled());
        $this->assertStringContainsString('application/json', $endpoint->getContentType());
        $this->assertJsonStringEqualsJsonString('{"schema_version":"v1"}', $endpoint->render());
    }

    public function testSecurityTxtEndpoint(): void
    {
        $config = $this->createMock(UcpConfig::class);
        $config->method('isSecurityTxtEnabled')->willReturn(true);
        $builder = $this->createMock(SecurityTxtBuilder::class);
        $builder->method('build')->willReturn("Contact: mailto:a@b.test\n");

        $endpoint = new SecurityTxtEndpoint($config, $builder);

        $this->assertSame('security.txt', $endpoint->getName());
        $this->assertTrue($endpoint->isEnabled());
        $this->assertStringContainsString('text/plain', $endpoint->getContentType());
        $this->assertSame("Contact: mailto:a@b.test\n", $endpoint->render());
    }
}
