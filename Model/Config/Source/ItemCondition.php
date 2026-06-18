<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * schema.org OfferItemCondition values for the Offer itemCondition field.
 */
class ItemCondition implements OptionSourceInterface
{
    /**
     * Options mapping admin labels to schema.org enum URLs.
     *
     * @return array<int, array<string, string>>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'https://schema.org/NewCondition',         'label' => 'New'],
            ['value' => 'https://schema.org/RefurbishedCondition', 'label' => 'Refurbished'],
            ['value' => 'https://schema.org/UsedCondition',        'label' => 'Used'],
            ['value' => 'https://schema.org/DamagedCondition',     'label' => 'Damaged'],
        ];
    }
}
