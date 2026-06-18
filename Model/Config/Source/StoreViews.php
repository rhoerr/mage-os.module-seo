<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * All active store views, for hreflang x-default / exclusion admin fields.
 */
class StoreViews implements OptionSourceInterface
{
    /**
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Active store views as id => label options.
     *
     * @return array<int, array<string, mixed>>
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->storeManager->getStores() as $store) {
            $options[] = [
                'value' => (int) $store->getId(),
                'label' => \sprintf('%s (%s)', $store->getName(), $store->getCode()),
            ];
        }

        return $options;
    }
}
