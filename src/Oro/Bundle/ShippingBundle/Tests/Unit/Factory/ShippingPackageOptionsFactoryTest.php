<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Factory;

use Oro\Bundle\ShippingBundle\Factory\ShippingPackageOptionsFactory;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\ShippingPackageOptions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use PHPUnit\Framework\TestCase;

class ShippingPackageOptionsFactoryTest extends TestCase
{
    public function testCreate()
    {
        $dimensions = Dimensions::create(0, 0, 0);
        $weight = Weight::create(0);

        static::assertEquals(
            new ShippingPackageOptions($dimensions, $weight),
            (new ShippingPackageOptionsFactory())->create($dimensions, $weight)
        );
    }
}
