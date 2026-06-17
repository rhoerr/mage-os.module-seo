<?php

declare(strict_types=1);

namespace MageOS\Seo\Plugin\Robots;

use Magento\Robots\Model\Robots;
use MageOS\Seo\Model\Config;
use MageOS\Seo\Model\Config\Source\AiBots;

/**
 * Appends per-user-agent AI crawler directives to robots.txt.
 *
 * For each known AI user-agent, emits an Allow or Disallow block depending on the configured
 * disallow list. Disabled by default; appends nothing when disabled so the store's robots.txt is
 * unchanged.
 */
class AppendAiDirectivesPlugin
{
    /**
     * @param Config $seoConfig
     * @param AiBots $aiBots
     */
    public function __construct(
        private readonly Config $seoConfig,
        private readonly AiBots $aiBots
    ) {
    }

    /**
     * Append AI crawler directives after the standard robots.txt content.
     *
     * @param Robots $subject
     * @param string $result
     * @return string
     */
    public function afterGetData(Robots $subject, string $result): string
    {
        $directives = $this->buildAiDirectives();
        if ($directives === '') {
            return $result;
        }

        return rtrim($result) . "\n\n" . $directives . "\n";
    }

    /**
     * Build the AI crawler directive block, or an empty string when disabled.
     *
     * @return string
     */
    public function buildAiDirectives(): string
    {
        if (!$this->seoConfig->isAiRobotsEnabled()) {
            return '';
        }

        $disallowed = $this->seoConfig->getAiDisallowedBots();

        $blocks = ['# AI crawlers (managed by MageOS_Seo)'];
        foreach ($this->aiBots->toOptionArray() as $option) {
            $bot  = (string) $option['value'];
            $rule = \in_array($bot, $disallowed, true) ? 'Disallow: /' : 'Allow: /';
            $blocks[] = "User-agent: {$bot}";
            $blocks[] = $rule;
            $blocks[] = '';
        }

        return trim(implode("\n", $blocks));
    }
}
