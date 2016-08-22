<?php

namespace Oro\Bundle\ShippingBundle\Extension;

use Oro\Bundle\ShippingBundle\Entity\FreightClassInterface;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptionsInterface;

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
