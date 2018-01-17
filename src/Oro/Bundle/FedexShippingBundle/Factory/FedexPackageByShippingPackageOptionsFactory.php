<?php

namespace Oro\Bundle\FedexShippingBundle\Factory;

use Oro\Bundle\ShippingBundle\Model\ShippingPackageOptionsInterface;

class FedexPackageByShippingPackageOptionsFactory implements FedexPackageByShippingPackageOptionsFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function create(ShippingPackageOptionsInterface $packageOptions): array
    {
        return [
            'GroupPackageCount' => 1,
            'Weight' => [
                'Value' => $packageOptions->getWeight(),
                'Units' => $packageOptions->getWeightUnitCode(),
            ],
            'Dimensions' => [
                'Length' => $packageOptions->getLength(),
                'Width' => $packageOptions->getWidth(),
                'Height' => $packageOptions->getHeight(),
                'Units' => $packageOptions->getDimensionsUnitCode(),
            ],
        ];
    }
}
