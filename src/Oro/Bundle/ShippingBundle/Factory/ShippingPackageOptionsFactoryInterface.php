<?php

namespace Oro\Bundle\ShippingBundle\Factory;

use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\ShippingPackageOptionsInterface;
use Oro\Bundle\ShippingBundle\Model\Weight;

interface ShippingPackageOptionsFactoryInterface
{
    /**
     * @param Dimensions $dimensions
     * @param Weight     $weight
     *
     * @return ShippingPackageOptionsInterface
     */
    public function create(Dimensions $dimensions, Weight $weight): ShippingPackageOptionsInterface;
}
