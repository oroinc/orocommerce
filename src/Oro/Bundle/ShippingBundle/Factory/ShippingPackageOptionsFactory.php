<?php

namespace Oro\Bundle\ShippingBundle\Factory;

use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\ShippingPackageOptions;
use Oro\Bundle\ShippingBundle\Model\ShippingPackageOptionsInterface;
use Oro\Bundle\ShippingBundle\Model\Weight;

class ShippingPackageOptionsFactory implements ShippingPackageOptionsFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function create(Dimensions $dimensions, Weight $weight): ShippingPackageOptionsInterface
    {
        return new ShippingPackageOptions($dimensions, $weight);
    }
}
