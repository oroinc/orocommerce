<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Factory;

use Oro\Bundle\FedexShippingBundle\Factory\FedexPackageByShippingPackageOptionsFactory;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\ShippingPackageOptions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use PHPUnit\Framework\TestCase;

class FedexPackageByShippingPackageOptionsFactoryTest extends TestCase
{
    /**
     * @dataProvider optionsDataProvider
     */
    public function testCreate(ShippingPackageOptions $packageOptions, array $expected)
    {
        static::assertEquals(
            $expected,
            (new FedexPackageByShippingPackageOptionsFactory())->create($packageOptions)
        );
    }

    public function optionsDataProvider(): array
    {
        return [
            'with all dimensions' => [
                new ShippingPackageOptions(
                    Dimensions::create(1, 2, 3, (new LengthUnit())->setCode('cm')),
                    Weight::create(4, (new WeightUnit())->setCode('kg'))
                ),
                [
                    'groupPackageCount' => 1,
                    'weight' => [
                        'value' => 4,
                        'units' => 'kg',
                    ],
                    'dimensions' => [
                        'length' => 1,
                        'width' => 2,
                        'height' => 3,
                        'units' => 'cm'
                    ]
                ]
            ],
            'with length dimension' => [
                new ShippingPackageOptions(
                    Dimensions::create(1, 0, 0, (new LengthUnit())->setCode('cm')),
                    Weight::create(4, (new WeightUnit())->setCode('kg'))
                ),
                [
                    'groupPackageCount' => 1,
                    'weight' => [
                        'value' => 4,
                        'units' => 'kg',
                    ],
                    'dimensions' => [
                        'length' => 1,
                        'width' => 0,
                        'height' => 0,
                        'units' => 'cm'
                    ]
                ]
            ],
            'without dimensions' => [
                new ShippingPackageOptions(
                    Dimensions::create(0, 0, 0, null),
                    Weight::create(4, (new WeightUnit())->setCode('kg'))
                ),
                [
                    'groupPackageCount' => 1,
                    'weight' => [
                        'value' => 4,
                        'units' => 'kg',
                    ]
                ]
            ],
        ];
    }
}
