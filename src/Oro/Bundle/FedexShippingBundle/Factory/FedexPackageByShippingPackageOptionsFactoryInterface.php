<?php

namespace Oro\Bundle\FedexShippingBundle\Factory;

use Oro\Bundle\ShippingBundle\Model\ShippingPackageOptionsInterface;

interface FedexPackageByShippingPackageOptionsFactoryInterface
{
    /**
     * @param ShippingPackageOptionsInterface $packageOptions
     *
     * @return array
     */
    public function create(ShippingPackageOptionsInterface $packageOptions): array;
}
