<?php

namespace Oro\Bundle\ShippingBundle\Extension;

use Oro\Bundle\ShippingBundle\Entity\FreightClassInterface;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptionsInterface;

/**
 * Defines the contract for extensions that determine freight class applicability.
 *
 * Implementations of this interface can provide custom logic to determine whether a specific freight class
 * is applicable for given product shipping options, allowing for flexible freight class filtering
 * based on product characteristics.
 */
interface FreightClassesExtensionInterface
{
    /**
     * @param FreightClassInterface $class
     * @param ProductShippingOptionsInterface $options
     * @return bool
     */
    public function isApplicable(FreightClassInterface $class, ProductShippingOptionsInterface $options);
}
