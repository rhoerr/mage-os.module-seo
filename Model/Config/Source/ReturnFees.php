<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * schema.org ReturnFeesEnumeration values for MerchantReturnPolicy returnFees.
 */
class ReturnFees implements OptionSourceInterface
{
    /**
     * Options mapping admin labels to schema.org enum URLs.
     *
     * @return array<int, array<string, string>>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'https://schema.org/FreeReturn', 'label' => 'Free return'],
            [
                'value' => 'https://schema.org/ReturnFeesCustomerResponsibility',
                'label' => 'Customer pays return shipping',
            ],
            ['value' => 'https://schema.org/ReturnShippingFees', 'label' => 'Return shipping fees apply'],
        ];
    }
}
