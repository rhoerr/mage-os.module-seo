<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Ucp;

/**
 * Builds the /.well-known/security.txt document (RFC 9116).
 *
 * Emits Contact, Expires, Preferred-Languages and (optionally) Policy fields from config.
 */
class SecurityTxtBuilder
{
    /**
     * @param UcpConfig $config
     */
    public function __construct(
        private readonly UcpConfig $config
    ) {
    }

    /**
     * Build the security.txt body.
     *
     * @return string
     */
    public function build(): string
    {
        $lines = [];

        $contact = $this->config->getSecurityContactEmail();
        if ($contact !== '') {
            // A bare email is normalised to a mailto: URI; an already-qualified URI is kept as-is.
            $lines[] = 'Contact: ' . (preg_match('/^[a-z][a-z0-9+.-]*:/i', $contact) ? $contact : 'mailto:' . $contact);
        }

        $expires = $this->config->getSecurityExpires();
        if ($expires !== '') {
            $lines[] = 'Expires: ' . $expires;
        }

        $lines[] = 'Preferred-Languages: en';

        $policy = $this->config->getSecurityPolicyUrl();
        if ($policy !== '') {
            $lines[] = 'Policy: ' . $policy;
        }

        return implode("\n", $lines) . "\n";
    }
}
