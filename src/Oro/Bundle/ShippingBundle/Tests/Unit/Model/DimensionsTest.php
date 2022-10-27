<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Model;

use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\DimensionsValue;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class DimensionsTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        static::assertPropertyAccessors(
            new Dimensions(),
            [
                ['value', new DimensionsValue()],
                ['unit', new LengthUnit()]
            ]
        );
    }

    public function testCreate()
    {
        $unit = new LengthUnit();

        $model = Dimensions::create(12, 34, 56, $unit);

        static::assertInstanceOf(Dimensions::class, $model);
        static::assertEquals(DimensionsValue::create(12, 34, 56), $model->getValue());
        static::assertSame($unit, $model->getUnit());
    }
}
