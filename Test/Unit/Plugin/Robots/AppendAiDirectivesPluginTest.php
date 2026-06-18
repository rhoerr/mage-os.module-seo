<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Plugin\Robots;

use MageOS\Seo\Model\Config;
use MageOS\Seo\Model\Config\Source\AiBots;
use MageOS\Seo\Plugin\Robots\AppendAiDirectivesPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AppendAiDirectivesPluginTest extends TestCase
{
    private Config&MockObject $config;
    private AppendAiDirectivesPlugin $plugin;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->plugin = new AppendAiDirectivesPlugin($this->config, new AiBots());
    }

    public function testReturnsEmptyWhenDisabled(): void
    {
        $this->config->method('isAiRobotsEnabled')->willReturn(false);
        $this->config->expects($this->never())->method('getAiDisallowedBots');

        $this->assertSame('', $this->plugin->buildAiDirectives());
    }

    public function testDisallowsConfiguredBotsAndAllowsTheRest(): void
    {
        $this->config->method('isAiRobotsEnabled')->willReturn(true);
        $this->config->method('getAiDisallowedBots')->willReturn(['CCBot', 'Bytespider']);

        $output = $this->plugin->buildAiDirectives();

        $this->assertStringContainsString('# AI crawlers (managed by MageOS_Seo)', $output);
        // Disallowed bots get a Disallow rule.
        $this->assertMatchesRegularExpression('/User-agent: CCBot\nDisallow: \//', $output);
        $this->assertMatchesRegularExpression('/User-agent: Bytespider\nDisallow: \//', $output);
        // A bot not in the disallow list is explicitly allowed.
        $this->assertMatchesRegularExpression('/User-agent: GPTBot\nAllow: \//', $output);
        $this->assertStringNotContainsString('User-agent: GPTBot' . "\n" . 'Disallow: /', $output);
    }

    public function testAllowsEveryBotWhenDisallowListEmpty(): void
    {
        $this->config->method('isAiRobotsEnabled')->willReturn(true);
        $this->config->method('getAiDisallowedBots')->willReturn([]);

        $output = $this->plugin->buildAiDirectives();

        $this->assertStringNotContainsString('Disallow: /', $output);
        $this->assertStringContainsString('Allow: /', $output);
    }

    public function testAfterGetDataLeavesResultUntouchedWhenDisabled(): void
    {
        $this->config->method('isAiRobotsEnabled')->willReturn(false);
        $robots = $this->createMock(\Magento\Robots\Model\Robots::class);

        $this->assertSame('Existing', $this->plugin->afterGetData($robots, 'Existing'));
    }

    public function testAfterGetDataAppendsDirectivesWhenEnabled(): void
    {
        $this->config->method('isAiRobotsEnabled')->willReturn(true);
        $this->config->method('getAiDisallowedBots')->willReturn(['CCBot']);
        $robots = $this->createMock(\Magento\Robots\Model\Robots::class);

        $result = $this->plugin->afterGetData($robots, "User-agent: *\nAllow: /");

        $this->assertStringStartsWith("User-agent: *\nAllow: /\n\n", $result);
        $this->assertStringContainsString('User-agent: CCBot', $result);
        $this->assertStringEndsWith("\n", $result);
    }
}
