<?php

namespace Oro\Bundle\ShippingBundle\Factory;

use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\ShippingPackageOptions;
use Oro\Bundle\ShippingBundle\Model\ShippingPackageOptionsInterface;
use Oro\Bundle\ShippingBundle\Model\Weight;

/**
 * Creates shipping package options instances from dimensions and weight data.
 *
 * This factory provides a simple implementation that creates {@see ShippingPackageOptions} instances
 * containing the physical characteristics of a shipping package.
 */
class ShippingPackageOptionsFactory implements ShippingPackageOptionsFactoryInterface
{
    #[\Override]
    public function create(Dimensions $dimensions, Weight $weight): ShippingPackageOptionsInterface
    {
        return new ShippingPackageOptions($dimensions, $weight);
    }
}
