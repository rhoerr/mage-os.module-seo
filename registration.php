<?php

declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'MageOS_Seo',
    __DIR__
);

// Magento < 2.4.7 has no ResetAfterRequestInterface; declare it so classes load.
if (!interface_exists(\Magento\Framework\ObjectManager\ResetAfterRequestInterface::class)) {
    require __DIR__ . '/Compat/ResetAfterRequestInterface.php';
}
