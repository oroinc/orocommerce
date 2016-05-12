<?php

namespace OroB2B\Bundle\ShippingBundle\Extension;

use OroB2B\Bundle\ShippingBundle\Entity\FreightClass;
use OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptionsInterface;

interface FreightClassesExtensionInterface
{
    /**
     * @param FreightClass $class
     * @param ProductShippingOptionsInterface $options
     * @return bool
     */
    public function isApplicable(FreightClass $class, ProductShippingOptionsInterface $options);
}
