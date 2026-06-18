<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * schema.org MerchantReturnEnumeration values for MerchantReturnPolicy returnPolicyCategory.
 */
class ReturnPolicyCategory implements OptionSourceInterface
{
    /**
     * Options mapping admin labels to schema.org enum URLs.
     *
     * @return array<int, array<string, string>>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'https://schema.org/MerchantReturnFiniteReturnWindow', 'label' => 'Finite return window'],
            ['value' => 'https://schema.org/MerchantReturnUnlimitedWindow',    'label' => 'Unlimited return window'],
            ['value' => 'https://schema.org/MerchantReturnNotPermitted',       'label' => 'Returns not permitted'],
        ];
    }
}
