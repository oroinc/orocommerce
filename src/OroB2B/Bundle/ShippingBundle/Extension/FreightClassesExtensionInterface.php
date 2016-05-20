<?php

namespace OroB2B\Bundle\ShippingBundle\Extension;

use OroB2B\Bundle\ShippingBundle\Entity\FreightClassInterface;
use OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptionsInterface;

interface FreightClassesExtensionInterface
{
    /**
     * @param FreightClassInterface $class
     * @param ProductShippingOptionsInterface $options
     * @return bool
     */
    public function isApplicable(FreightClassInterface $class, ProductShippingOptionsInterface $options);
}
