<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Model;

use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\ShippingPackageOptions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use PHPUnit\Framework\TestCase;

class ShippingPackageOptionsTest extends TestCase
{
    public function testGettersUnitsAreNull()
    {
        $options = new ShippingPackageOptions(
            Dimensions::create(1.1, 2.2, 3.3),
            Weight::create(4.4)
        );

        static::assertSame(4.4, $options->getWeight());
        static::assertSame('', $options->getWeightUnitCode());
        static::assertSame(1.1, $options->getLength());
        static::assertSame(2.2, $options->getWidth());
        static::assertSame(3.3, $options->getHeight());
        static::assertSame('', $options->getDimensionsUnitCode());
        static::assertSame(1.1 + 2 * 2.2 + 2 * 3.3, $options->getGirth());
    }

    public function testGettersUnitsNotNull()
    {
        $options = new ShippingPackageOptions(
            Dimensions::create(1.1, 2.2, 3.3, (new LengthUnit())->setCode('cm')),
            Weight::create(4.4, (new WeightUnit())->setCode('lb'))
        );

        static::assertSame(4.4, $options->getWeight());
        static::assertSame('lb', $options->getWeightUnitCode());
        static::assertSame(1.1, $options->getLength());
        static::assertSame(2.2, $options->getWidth());
        static::assertSame(3.3, $options->getHeight());
        static::assertSame('cm', $options->getDimensionsUnitCode());
        static::assertSame(1.1 + 2 * 2.2 + 2 * 3.3, $options->getGirth());
    }
}
