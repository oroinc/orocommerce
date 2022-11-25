<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Model;

use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class WeightTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    /** @var Weight */
    private $model;

    protected function setUp(): void
    {
        $this->model = new Weight();
    }

    public function testGettersAndSetters()
    {
        self::assertPropertyAccessors(
            $this->model,
            [
                ['value', 42.5],
                ['unit', new WeightUnit()]
            ]
        );
    }
}
