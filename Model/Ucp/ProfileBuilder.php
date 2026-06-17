<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Ucp;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * Builds the /.well-known/ucp manifest (Universal Commerce Protocol profile).
 *
 * Capabilities are advertised only when their config toggle is on; the signing_keys array is
 * included only when a public key JWK has been generated. The builder refuses to emit a manifest
 * that contains a private key component (a "d" member in any JWK).
 */
class ProfileBuilder
{
    private const SPEC_VERSION = '2026-04-08';

    /**
     * @param UcpConfig $config
     * @param CapabilityPool $capabilityPool
     */
    public function __construct(
        private readonly UcpConfig $config,
        private readonly CapabilityPool $capabilityPool
    ) {
    }

    /**
     * Build the UCP manifest as an associative array.
     *
     * @throws LocalizedException When the stored public JWK leaks a private key component.
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $baseUrl = $this->config->getBaseUrl();

        $manifest = [
            '$schema'  => 'https://json-schema.org',
            'version'  => self::SPEC_VERSION,
            'merchant' => [
                'id'     => $this->config->getMerchantId(),
                'name'   => $this->config->getMerchantName(),
                'domain' => $baseUrl,
            ],
            'transports' => [
                'rest' => [
                    'baseUrl' => $baseUrl,
                ],
            ],
            'capabilities' => $this->buildCapabilities(),
        ];

        $keys = $this->buildSigningKeys();
        if ($keys !== []) {
            $manifest['signing_keys'] = $keys;
        }

        return $manifest;
    }

    /**
     * Build the capabilities map from config toggles plus any registered capability providers.
     *
     * @return array<string, mixed>
     */
    private function buildCapabilities(): array
    {
        $shopping = ['enabled' => true];
        foreach ($this->config->getEnabledCapabilities() as $capability) {
            $shopping[$capability] = ['enabled' => true];
        }

        $capabilities = ['dev.ucp.shopping' => $shopping];

        foreach ($this->capabilityPool->getEnabledCapabilities() as $key => $data) {
            $capabilities[$key] = $data;
        }

        return $capabilities;
    }

    /**
     * Parse and validate the stored public key JWK.
     *
     * @throws LocalizedException When the JWK contains a private key component.
     * @return array<int, array<string, mixed>>
     */
    private function buildSigningKeys(): array
    {
        $raw = $this->config->getPublicKeyJwk();
        if ($raw === '') {
            return [];
        }

        $jwk = json_decode($raw, true);
        if (!\is_array($jwk)) {
            return [];
        }

        if (\array_key_exists('d', $jwk)) {
            throw new LocalizedException(
                new Phrase('UCP signing key is misconfigured: the stored public JWK contains a private key.')
            );
        }

        return [$jwk];
    }
}
