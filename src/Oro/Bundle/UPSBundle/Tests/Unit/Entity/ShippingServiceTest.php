<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Entity;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ShippingServiceTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        static::assertPropertyAccessors(new ShippingService(), [
            ['code', 'some code'],
            ['description', 'some description'],
            ['country', new Country('US')]
        ]);
    }
    
    public function testToString()
    {
        $entity = new ShippingService();
        $entity->setCode('03')->setDescription('UPS Ground');
        static::assertEquals('03: UPS Ground', (string)$entity);
    }
}
