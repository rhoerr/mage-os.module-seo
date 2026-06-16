<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Pool;

/**
 * Shared layout-handle matching for provider pools.
 *
 * A provider declares the layout handles it applies to via getHandles(). This collaborator
 * decides whether a provider runs on the current page: the wildcard '*' matches every page,
 * otherwise at least one provider handle must be present in the active layout handles.
 *
 * Extracted from the duplicated handlesMatch() logic in the StructuredData, MetaTag and
 * PageTitle compositors so every pool shares one implementation (and one unit test).
 */
class HandleMatcher
{
    /**
     * Whether a provider's handles match the current page's active layout handles.
     *
     * @param string[] $providerHandles
     * @param string[] $activeHandles
     * @return bool
     */
    public function matches(array $providerHandles, array $activeHandles): bool
    {
        if (\in_array('*', $providerHandles, true)) {
            return true;
        }
        return !empty(array_intersect($providerHandles, $activeHandles));
    }
}
