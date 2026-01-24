<?php

namespace Oro\Bundle\ShippingBundle\Extension;

use Oro\Bundle\ShippingBundle\Entity\FreightClassInterface;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptionsInterface;

/**
 * Default implementation of freight class applicability logic.
 *
 * This extension determines that only the 'parcel' freight class is applicable for all products,
 * providing a basic freight class filtering mechanism.
 */
class FreightClassesExtension implements FreightClassesExtensionInterface
{
    #[\Override]
    public function isApplicable(FreightClassInterface $class, ProductShippingOptionsInterface $options)
    {
        return $class->getCode() === 'parcel';
    }
}
