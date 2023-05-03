<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Entity;

use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class WeightUnitTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    /** @var WeightUnit */
    private $entity;

    protected function setUp(): void
    {
        $this->entity = new WeightUnit();
    }

    public function testGettersAndSetters()
    {
        $properties = [
            ['code', '123'],
            ['conversionRates', ['rate1' => 'rateValue']],
        ];

        $this->assertPropertyAccessors($this->entity, $properties);
    }

    public function testToString()
    {
        $this->entity->setCode('test');

        $this->assertEquals('test', (string)$this->entity);
    }
}
