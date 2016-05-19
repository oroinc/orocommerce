<?php

namespace OroB2B\Bundle\ShippingBundle\Extension;

use OroB2B\Bundle\ShippingBundle\Entity\FreightClassInterface;
use OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptionsInterface;

class FreightClassesExtension implements FreightClassesExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function isApplicable(FreightClassInterface $class, ProductShippingOptionsInterface $options)
    {
        return $class->getCode() === 'parcel';
    }
}
