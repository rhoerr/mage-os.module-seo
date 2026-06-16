<?php

declare(strict_types=1);

namespace MageOS\Seo\Model;

use Magento\Framework\Model\AbstractModel;
use MageOS\Seo\Api\Data\OrganisationInterface;
use MageOS\Seo\Model\ResourceModel\Organisation as OrganisationResource;

class Organisation extends AbstractModel implements OrganisationInterface
{
    /**
     * Initialize resource model.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(OrganisationResource::class);
    }

    /**
     * @inheritdoc
     */
    public function getEntityId(): int
    {
        return (int) $this->getData(self::ENTITY_ID);
    }

    /**
     * @inheritdoc
     */
    public function getScope(): string
    {
        return (string) ($this->getData(self::SCOPE) ?: 'default');
    }

    /**
     * @inheritdoc
     */
    public function setScope(string $scope): OrganisationInterface
    {
        return $this->setData(self::SCOPE, $scope);
    }

    /**
     * @inheritdoc
     */
    public function getScopeId(): int
    {
        return (int) $this->getData(self::SCOPE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setScopeId(int $scopeId): OrganisationInterface
    {
        return $this->setData(self::SCOPE_ID, $scopeId);
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return (string) $this->getData(self::NAME);
    }

    /**
     * @inheritdoc
     */
    public function setName(string $name): OrganisationInterface
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * @inheritdoc
     */
    public function getUrl(): string
    {
        return (string) $this->getData(self::URL);
    }

    /**
     * @inheritdoc
     */
    public function setUrl(string $url): OrganisationInterface
    {
        return $this->setData(self::URL, $url);
    }

    /**
     * @inheritdoc
     */
    public function getLogoPath(): string
    {
        return (string) $this->getData(self::LOGO_PATH);
    }

    /**
     * @inheritdoc
     */
    public function setLogoPath(string $logoPath): OrganisationInterface
    {
        return $this->setData(self::LOGO_PATH, $logoPath);
    }

    /**
     * @inheritdoc
     */
    public function getLogoWidth(): int
    {
        return (int) $this->getData(self::LOGO_WIDTH);
    }

    /**
     * @inheritdoc
     */
    public function setLogoWidth(int $width): OrganisationInterface
    {
        return $this->setData(self::LOGO_WIDTH, $width);
    }

    /**
     * @inheritdoc
     */
    public function getLogoHeight(): int
    {
        return (int) $this->getData(self::LOGO_HEIGHT);
    }

    /**
     * @inheritdoc
     */
    public function setLogoHeight(int $height): OrganisationInterface
    {
        return $this->setData(self::LOGO_HEIGHT, $height);
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): string
    {
        return (string) $this->getData(self::DESCRIPTION);
    }

    /**
     * @inheritdoc
     */
    public function setDescription(string $description): OrganisationInterface
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    /**
     * @inheritdoc
     */
    public function getSocialProfiles(): array
    {
        $raw = $this->getData(self::SOCIAL_PROFILES);
        if (empty($raw)) {
            return [];
        }
        return \is_array($raw) ? $raw : (json_decode($raw, true) ?? []);
    }

    /**
     * @inheritdoc
     */
    public function setSocialProfiles(array $profiles): OrganisationInterface
    {
        return $this->setData(self::SOCIAL_PROFILES, json_encode($profiles));
    }

    /**
     * @inheritdoc
     */
    public function getContactPoint(): array
    {
        $raw = $this->getData(self::CONTACT_POINT);
        if (empty($raw)) {
            return [];
        }
        return \is_array($raw) ? $raw : (json_decode($raw, true) ?? []);
    }

    /**
     * @inheritdoc
     */
    public function setContactPoint(array $contactPoint): OrganisationInterface
    {
        return $this->setData(self::CONTACT_POINT, json_encode($contactPoint));
    }

    /**
     * @inheritdoc
     */
    public function getOrgType(): string
    {
        return (string) ($this->getData(self::ORG_TYPE) ?: 'Organization');
    }

    /**
     * @inheritdoc
     */
    public function setOrgType(string $type): OrganisationInterface
    {
        return $this->setData(self::ORG_TYPE, $type);
    }

    /**
     * @inheritdoc
     */
    public function getAddress(): array
    {
        return [
            'street_address'   => (string) $this->getData(self::STREET_ADDRESS),
            'address_locality' => (string) $this->getData(self::ADDRESS_LOCALITY),
            'address_region'   => (string) $this->getData(self::ADDRESS_REGION),
            'postal_code'      => (string) $this->getData(self::POSTAL_CODE),
            'address_country'  => (string) $this->getData(self::ADDRESS_COUNTRY),
        ];
    }

    /**
     * @inheritdoc
     */
    public function setLocalPresence(array $data): OrganisationInterface
    {
        $fields = [
            self::STREET_ADDRESS,
            self::ADDRESS_LOCALITY,
            self::ADDRESS_REGION,
            self::POSTAL_CODE,
            self::ADDRESS_COUNTRY,
            self::TELEPHONE,
            self::EMAIL,
            self::LATITUDE,
            self::LONGITUDE,
            self::PRICE_RANGE,
        ];
        foreach ($fields as $field) {
            if (\array_key_exists($field, $data)) {
                $this->setData($field, (string) $data[$field]);
            }
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getLatitude(): string
    {
        return (string) $this->getData(self::LATITUDE);
    }

    /**
     * @inheritdoc
     */
    public function getLongitude(): string
    {
        return (string) $this->getData(self::LONGITUDE);
    }

    /**
     * @inheritdoc
     */
    public function getTelephone(): string
    {
        return (string) $this->getData(self::TELEPHONE);
    }

    /**
     * @inheritdoc
     */
    public function getEmail(): string
    {
        return (string) $this->getData(self::EMAIL);
    }

    /**
     * @inheritdoc
     */
    public function getPriceRange(): string
    {
        return (string) $this->getData(self::PRICE_RANGE);
    }
}
