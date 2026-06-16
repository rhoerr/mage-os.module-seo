<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\StructuredData\Provider;

use MageOS\Seo\Api\StructuredDataProviderInterface;
use MageOS\Seo\Model\Config;

/**
 * Emits Speakable structured data marking page sections for voice/audio assistants.
 *
 * Disabled by default. When enabled, outputs a WebPage node with a SpeakableSpecification listing
 * the configured CSS selectors.
 */
class SpeakableProvider implements StructuredDataProviderInterface
{
    /**
     * @param Config $seoConfig
     */
    public function __construct(
        private readonly Config $seoConfig
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getHandles(): array
    {
        return ['*'];
    }

    /**
     * @inheritdoc
     */
    public function getSchemas(): array
    {
        if (!$this->seoConfig->isSpeakableEnabled()) {
            return [];
        }

        $selectors = $this->seoConfig->getSpeakableCssSelectors();
        if ($selectors === []) {
            return [];
        }

        return [[
            '@context'  => 'https://schema.org',
            '@type'     => 'WebPage',
            'speakable' => [
                '@type'       => 'SpeakableSpecification',
                'cssSelector' => $selectors,
            ],
        ]];
    }
}
