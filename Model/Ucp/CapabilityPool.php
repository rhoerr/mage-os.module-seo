<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Ucp;

use MageOS\Seo\Api\UcpCapabilityProviderInterface;

/**
 * Collect-all pool of UCP capability providers feeding the manifest.
 *
 * Empty by default; Phase 2 endpoint modules append their providers via di.xml.
 */
class CapabilityPool
{
    /**
     * @param UcpCapabilityProviderInterface[] $providers
     */
    public function __construct(
        private readonly array $providers = []
    ) {
    }

    /**
     * Return capability descriptors keyed by capability key for every enabled provider.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getEnabledCapabilities(): array
    {
        $capabilities = [];
        foreach ($this->providers as $provider) {
            if ($provider->isEnabled()) {
                $capabilities[$provider->getCapabilityKey()] = $provider->getCapabilityData();
            }
        }

        return $capabilities;
    }
}
