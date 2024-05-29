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
            'groupPackageCount' => 1,
            'weight' => [
                'value' => $packageOptions->getWeight(),
                'units' => $packageOptions->getWeightUnitCode(),
            ]
        ];

        if ($packageOptions->getLength() || $packageOptions->getWidth() || $packageOptions->getHeight()) {
            $data['dimensions'] = [
                'length' => $packageOptions->getLength(),
                'width' => $packageOptions->getWidth(),
                'height' => $packageOptions->getHeight(),
                'units' => $packageOptions->getDimensionsUnitCode(),
            ];
        }

        return $data;
    }
}
