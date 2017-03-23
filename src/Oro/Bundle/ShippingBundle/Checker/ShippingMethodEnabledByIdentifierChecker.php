<?php

namespace Oro\Bundle\ShippingBundle\Checker;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;

class ShippingMethodEnabledByIdentifierChecker implements ShippingMethodEnabledByIdentifierCheckerInterface
{
    /**
     * @var ShippingMethodRegistry
     */
    private $registry;

    /**
     * @param ShippingMethodRegistry $registry
     */
    public function __construct(ShippingMethodRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritDoc}
     */
    public function isEnabled($identifier)
    {
        return $this->registry->getShippingMethod($identifier) !== null ?
            $this->registry->getShippingMethod($identifier)->isEnabled() :
            false;
    }
}
