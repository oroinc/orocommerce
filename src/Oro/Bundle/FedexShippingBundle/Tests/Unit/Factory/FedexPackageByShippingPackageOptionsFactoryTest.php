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
    public function testCreate()
    {
        $packageOptions = new ShippingPackageOptions(
            Dimensions::create(1, 2, 3, (new LengthUnit())->setCode('cm')),
            Weight::create(4, (new WeightUnit())->setCode('kg'))
        );

        static::assertEquals(
            [
                'GroupPackageCount' => 1,
                'Weight' => [
                    'Value' => 4,
                    'Units' => 'kg',
                ],
                'Dimensions' => [
                    'Length' => 1,
                    'Width' => 2,
                    'Height' => 3,
                    'Units' => 'cm',
                ],
            ],
            (new FedexPackageByShippingPackageOptionsFactory())->create($packageOptions)
        );
    }
}
