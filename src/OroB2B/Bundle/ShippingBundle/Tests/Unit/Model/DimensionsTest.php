<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Model;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\ShippingBundle\Entity\LengthUnit;
use OroB2B\Bundle\ShippingBundle\Model\Dimensions;
use OroB2B\Bundle\ShippingBundle\Model\DimensionsValue;

class DimensionsTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var Dimensions */
    protected $model;

    protected function setUp()
    {
        $this->model = new Dimensions();
    }

    protected function tearDown()
    {
        unset($this->model);
    }

    public function testAccessors()
    {
        static::assertPropertyAccessors(
            $this->model,
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

        $this->assertInstanceOf('OroB2B\Bundle\ShippingBundle\Model\Dimensions', $model);
        $this->assertAttributeEquals(DimensionsValue::create(12, 34, 56), 'value', $model);
        $this->assertAttributeSame($unit, 'unit', $model);
    }
}
