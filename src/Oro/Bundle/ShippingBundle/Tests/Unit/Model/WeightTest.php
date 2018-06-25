<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Model;

use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class WeightTest extends \PHPUnit\Framework\TestCase
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
