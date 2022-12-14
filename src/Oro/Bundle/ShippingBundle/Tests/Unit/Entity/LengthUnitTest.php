<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Entity;

use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class LengthUnitTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    /** @var LengthUnit */
    private $entity;

    protected function setUp(): void
    {
        $this->entity = new LengthUnit();
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
