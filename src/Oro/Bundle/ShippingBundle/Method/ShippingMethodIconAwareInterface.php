<?php

namespace Oro\Bundle\ShippingBundle\Method;

interface ShippingMethodIconAwareInterface
{
    /**
     * Returns icon path for UI, should return value like 'bundles/acmedemo/img/logo.png'.
     *
     * @return string|null
     */
    public function getIcon();
}
