<?php

namespace Oro\Bundle\ShippingBundle\Checker;

interface ShippingMethodEnabledByIdentifierCheckerInterface
{
    /**
     * @param string $identifier
     *
     * @return bool
     */
    public function isEnabled($identifier);
}
