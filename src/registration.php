<?php

/**
 * Force Order Cancel Status Module
 *
 * @author  Peter McWilliams <pmcwilliams@augustash.com>
 * @license MIT
 */

declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Augustash_ForceCancel',
    __DIR__
);
