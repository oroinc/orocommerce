<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Model;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\ShippingBundle\Entity\LengthUnit;
use OroB2B\Bundle\ShippingBundle\Model\Dimensions;

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
                ['length', 12.3],
                ['width', 45.6],
                ['height', 78.9],
                ['unit', new LengthUnit()]
            ]
        );
    }
}
