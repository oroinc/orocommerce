<?php

namespace Oro\Bundle\ShippingBundle\Method;

interface ShippingMethodIconAwareInterface
{
    /**
     * Returns icon path for UI, should return value like 'bundles/acmedemo/img/logo.png'
     * Relative path to assets helper
     *
     * @return string
     */
    public function getIcon();
}
