<?php

namespace Oro\Bundle\ShippingBundle\Extension;

use Oro\Bundle\ShippingBundle\Entity\FreightClassInterface;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptionsInterface;

interface FreightClassesExtensionInterface
{
    /**
     * @param FreightClassInterface $class
     * @param ProductShippingOptionsInterface $options
     * @return bool
     */
    public function isApplicable(FreightClassInterface $class, ProductShippingOptionsInterface $options);
}
