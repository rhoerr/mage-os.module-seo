<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\WellKnown\Endpoint;

use MageOS\Seo\Api\WellKnownEndpointInterface;
use MageOS\Seo\Model\Ucp\AiPluginBuilder;
use MageOS\Seo\Model\Ucp\UcpConfig;

/**
 * Serves /.well-known/ai-plugin.json — the OpenAI plugin discovery manifest.
 */
class AiPluginEndpoint implements WellKnownEndpointInterface
{
    /**
     * @param UcpConfig $config
     * @param AiPluginBuilder $builder
     */
    public function __construct(
        private readonly UcpConfig $config,
        private readonly AiPluginBuilder $builder
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'ai-plugin.json';
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(): bool
    {
        return $this->config->isAiPluginEnabled();
    }

    /**
     * @inheritDoc
     */
    public function getContentType(): string
    {
        return 'application/json; charset=utf-8';
    }

    /**
     * @inheritDoc
     */
    public function getCacheControl(): string
    {
        return 'public, max-age=300';
    }

    /**
     * @inheritDoc
     */
    public function render(): string
    {
        return (string) json_encode($this->builder->build(), JSON_UNESCAPED_SLASHES);
    }
}
