<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\WellKnown\Endpoint;

use MageOS\Seo\Api\WellKnownEndpointInterface;
use MageOS\Seo\Model\Ucp\SecurityTxtBuilder;
use MageOS\Seo\Model\Ucp\UcpConfig;

/**
 * Serves /.well-known/security.txt — RFC 9116 security contact disclosure.
 */
class SecurityTxtEndpoint implements WellKnownEndpointInterface
{
    /**
     * @param UcpConfig $config
     * @param SecurityTxtBuilder $builder
     */
    public function __construct(
        private readonly UcpConfig $config,
        private readonly SecurityTxtBuilder $builder
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'security.txt';
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(): bool
    {
        return $this->config->isSecurityTxtEnabled();
    }

    /**
     * @inheritDoc
     */
    public function getContentType(): string
    {
        return 'text/plain; charset=utf-8';
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
        return $this->builder->build();
    }
}
