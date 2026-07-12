<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Cache;

/**
 * Resolves cache cleaning-mode identifiers across supported Magento versions.
 *
 * Magento\Framework\Cache\CacheConstants only exists on recent Magento
 * (2.4.9+ / Mage-OS); on older supported targets (down to 2.4.6) the same
 * values are the Zend_Cache constants. Both define the identical string —
 * the literal is the stable contract the cache backends actually receive —
 * so the platform constant is preferred when present and the literal used
 * otherwise.
 */
class CleaningMode
{
    private const MATCHING_ANY_TAG = 'matchingAnyTag';

    /**
     * Cleaning mode that removes entries carrying any of the given tags.
     *
     * @return string
     */
    public function matchingAnyTag(): string
    {
        if (class_exists(\Magento\Framework\Cache\CacheConstants::class)) {
            return \Magento\Framework\Cache\CacheConstants::CLEANING_MODE_MATCHING_ANY_TAG;
        }

        // Pre-2.4.9: equals \Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG.
        return self::MATCHING_ANY_TAG;
    }
}
