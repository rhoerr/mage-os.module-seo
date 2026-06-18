<?php

declare(strict_types=1);

namespace MageOS\Seo\Api;

/**
 * Contract for a UCP capability advertised in the /.well-known/ucp profile.
 *
 * Phase 2 bridge modules (catalog/cart/checkout endpoints) register a provider via their own
 * di.xml to declare a live capability and its endpoint metadata, without editing MageOS_Seo.
 */
interface UcpCapabilityProviderInterface
{
    /**
     * The UCP capability key (e.g. "dev.ucp.shopping.catalog").
     *
     * @return string
     */
    public function getCapabilityKey(): string;

    /**
     * Whether this capability is currently advertised.
     *
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * The capability descriptor merged into the manifest under its key.
     *
     * @return array<string, mixed>
     */
    public function getCapabilityData(): array;
}
