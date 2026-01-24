<?php

namespace Oro\Bundle\FedexShippingBundle\Factory;

use Oro\Bundle\ShippingBundle\Model\ShippingPackageOptionsInterface;

/**
 * Defines the contract for creating FedEx packages from shipping package options.
 */
interface FedexPackageByShippingPackageOptionsFactoryInterface
{
    public function create(ShippingPackageOptionsInterface $packageOptions): array;
}
