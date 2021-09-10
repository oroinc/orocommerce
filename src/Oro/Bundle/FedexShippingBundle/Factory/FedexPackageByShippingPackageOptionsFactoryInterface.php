<?php

namespace Oro\Bundle\FedexShippingBundle\Factory;

use Oro\Bundle\ShippingBundle\Model\ShippingPackageOptionsInterface;

interface FedexPackageByShippingPackageOptionsFactoryInterface
{
    public function create(ShippingPackageOptionsInterface $packageOptions): array;
}
