<?php
/**
 * Custom Shipping Method Module Registration
 */

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'CustomShipping_Method',
    __DIR__
);