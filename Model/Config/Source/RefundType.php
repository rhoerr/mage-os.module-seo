<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * schema.org RefundTypeEnumeration values for MerchantReturnPolicy refundType.
 */
class RefundType implements OptionSourceInterface
{
    /**
     * Options mapping admin labels to schema.org enum URLs.
     *
     * @return array<int, array<string, string>>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'https://schema.org/FullRefund',        'label' => 'Full refund'],
            ['value' => 'https://schema.org/ExchangeRefund',    'label' => 'Exchange'],
            ['value' => 'https://schema.org/StoreCreditRefund', 'label' => 'Store credit'],
        ];
    }
}
