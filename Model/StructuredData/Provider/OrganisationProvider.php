<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\StructuredData\Provider;

use Magento\Store\Model\StoreManagerInterface;
use MageOS\Seo\Api\OrganisationRepositoryInterface;
use MageOS\Seo\Api\StructuredDataProviderInterface;
use MageOS\Seo\Model\StructuredData\OrganisationId;

class OrganisationProvider implements StructuredDataProviderInterface
{
    /**
     * @param OrganisationRepositoryInterface $organisationRepository
     * @param StoreManagerInterface $storeManager
     * @param OrganisationId $organisationId
     */
    public function __construct(
        private readonly OrganisationRepositoryInterface $organisationRepository,
        private readonly StoreManagerInterface           $storeManager,
        private readonly OrganisationId                  $organisationId
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
        $storeId   = (int) $this->storeManager->getStore()->getId();
        $websiteId = (int) $this->storeManager->getWebsite()->getId();
        $org       = $this->organisationRepository->getForScope($storeId, $websiteId);

        if ($org->getName() === '') {
            return [];
        }

        $baseUrl = rtrim($org->getUrl(), '/');
        $orgId   = $this->organisationId->fromUrl($org->getUrl());

        $orgSchema = [
            '@context' => 'https://schema.org',
            '@type'    => $org->getOrgType(),
            '@id'      => $orgId,
            'name'     => $org->getName(),
            'url'      => $baseUrl,
        ];

        // Logo
        if ($org->getLogoPath() !== '') {
            $logoNode = [
                '@type' => 'ImageObject',
                'url'   => $org->getLogoPath(),
            ];
            if ($org->getLogoWidth() > 0) {
                $logoNode['width'] = $org->getLogoWidth();
            }
            if ($org->getLogoHeight() > 0) {
                $logoNode['height'] = $org->getLogoHeight();
            }
            $orgSchema['logo'] = $logoNode;
        }

        if ($org->getDescription() !== '') {
            $orgSchema['description'] = $org->getDescription();
        }

        $socials = $org->getSocialProfiles();
        if (!empty($socials)) {
            $orgSchema['sameAs'] = array_values($socials);
        }

        $contact = $org->getContactPoint();
        if (!empty($contact)) {
            $orgSchema['contactPoint'] = array_merge(
                ['@type' => 'ContactPoint'],
                $contact
            );
        }

        // LocalBusiness presence fields (emitted when populated; @type comes from org_type).
        $orgSchema = $this->addLocalPresence($orgSchema, $org);

        // WebSite with SearchAction
        $websiteSchema = [
            '@context'        => 'https://schema.org',
            '@type'           => 'WebSite',
            '@id'             => $baseUrl . '/#website',
            'name'            => $org->getName(),
            'url'             => $baseUrl,
            'publisher'       => ['@id' => $orgId],
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => [
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => $baseUrl . '/catalogsearch/result?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];

        return [$orgSchema, $websiteSchema];
    }

    /**
     * Append LocalBusiness presence fields (address, geo, contact, price range) when populated.
     *
     * @param array<string,mixed> $orgSchema
     * @param \MageOS\Seo\Api\Data\OrganisationInterface $org
     * @return array<string, mixed>
     */
    private function addLocalPresence(array $orgSchema, \MageOS\Seo\Api\Data\OrganisationInterface $org): array
    {
        $address = array_filter($org->getAddress(), static fn (string $v): bool => $v !== '');
        if ($address !== []) {
            $postal = ['@type' => 'PostalAddress'];
            $map = [
                'streetAddress'   => 'street_address',
                'addressLocality' => 'address_locality',
                'addressRegion'   => 'address_region',
                'postalCode'      => 'postal_code',
                'addressCountry'  => 'address_country',
            ];
            foreach ($map as $schemaKey => $dataKey) {
                if (!empty($address[$dataKey])) {
                    $postal[$schemaKey] = $address[$dataKey];
                }
            }
            $orgSchema['address'] = $postal;
        }

        $latitude  = $org->getLatitude();
        $longitude = $org->getLongitude();
        if ($latitude !== '' && $longitude !== '') {
            $orgSchema['geo'] = [
                '@type'     => 'GeoCoordinates',
                'latitude'  => $latitude,
                'longitude' => $longitude,
            ];
        }

        if ($org->getTelephone() !== '') {
            $orgSchema['telephone'] = $org->getTelephone();
        }
        if ($org->getEmail() !== '') {
            $orgSchema['email'] = $org->getEmail();
        }
        if ($org->getPriceRange() !== '') {
            $orgSchema['priceRange'] = $org->getPriceRange();
        }

        return $orgSchema;
    }
}
