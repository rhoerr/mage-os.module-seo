<?php
declare(strict_types=1);

// phpcs:ignoreFile -- polyfill: declares a framework interface absent before Magento 2.4.7
namespace Magento\Framework\ObjectManager;

interface ResetAfterRequestInterface
{
    /**
     * Reset request-scoped state (called by the framework Resetter on 2.4.7+).
     */
    public function _resetState(): void;
}
