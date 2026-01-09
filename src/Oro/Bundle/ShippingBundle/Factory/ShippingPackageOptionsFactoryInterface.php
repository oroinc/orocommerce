<?php

namespace Oro\Bundle\ShippingBundle\Factory;

use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\ShippingPackageOptionsInterface;
use Oro\Bundle\ShippingBundle\Model\Weight;

/**
 * Defines the contract for factories that create shipping package options instances.
 *
 * Implementations of this interface are responsible for creating {@see ShippingPackageOptionsInterface} instances
 * from dimensions and weight data, encapsulating the package characteristics needed for shipping cost calculation.
 */
interface ShippingPackageOptionsFactoryInterface
{
    public function create(Dimensions $dimensions, Weight $weight): ShippingPackageOptionsInterface;
}
