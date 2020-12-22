<?php

namespace Oro\Bundle\FedexShippingBundle\Factory;

use Oro\Bundle\ShippingBundle\Model\ShippingPackageOptionsInterface;

/**
 * Create shipping package data by shipping options.
 */
class FedexPackageByShippingPackageOptionsFactory implements FedexPackageByShippingPackageOptionsFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function create(ShippingPackageOptionsInterface $packageOptions): array
    {
        $data = [
            'GroupPackageCount' => 1,
            'Weight' => [
                'Value' => $packageOptions->getWeight(),
                'Units' => $packageOptions->getWeightUnitCode(),
            ]
        ];

        if ($packageOptions->getLength() || $packageOptions->getWidth() || $packageOptions->getHeight()) {
            $data['Dimensions'] = [
                'Length' => $packageOptions->getLength(),
                'Width' => $packageOptions->getWidth(),
                'Height' => $packageOptions->getHeight(),
                'Units' => $packageOptions->getDimensionsUnitCode(),
            ];
        }

        return $data;
    }
}
