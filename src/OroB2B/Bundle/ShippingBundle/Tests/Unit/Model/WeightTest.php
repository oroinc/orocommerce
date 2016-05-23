<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Model;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\ShippingBundle\Entity\WeightUnit;
use OroB2B\Bundle\ShippingBundle\Model\Weight;

class WeightTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var Weight */
    protected $model;

    protected function setUp()
    {
        $this->model = new Weight();
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
                ['value', 42.5],
                ['unit', new WeightUnit()]
            ]
        );
    }
}
