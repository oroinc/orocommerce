<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Entity;

use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ShippingServiceTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        static::assertPropertyAccessors(new ShippingService(), [
            ['code', 'some code'],
            ['description', 'some description'],
            ['expressService', false],
        ]);
    }

    public function testToString()
    {
        $entity = new ShippingService();
        $entity->setCode('Classic')->setDescription('DPD Classic');
        static::assertEquals('DPD Classic', (string) $entity);
    }

    public function testIsExpress()
    {
        $entity = new ShippingService();
        $entity
            ->setCode('Express_830')
            ->setDescription('DPD Express')
            ->setExpressService(true);
        static::assertTrue($entity->isExpressService());
    }
}
