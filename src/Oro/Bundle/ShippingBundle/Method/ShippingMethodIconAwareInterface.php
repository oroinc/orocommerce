<?php

namespace Oro\Bundle\ShippingBundle\Method;

/**
 * This interface should be implemented by shipping methods that can have an icon.
 */
interface ShippingMethodIconAwareInterface
{
    /**
     * Returns icon path for UI, should return value like 'bundles/acmedemo/img/logo.png'.
     */
    public function getIcon(): ?string;
}
