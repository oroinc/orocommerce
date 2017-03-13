<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\Factory;

use Oro\Bundle\ShippingBundle\Method\Event\MethodTypeChangeEvent;
use Oro\Bundle\ShippingBundle\Method\Factory\MethodTypeChangeEventFactory;

class MethodTypeChangeEventFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $availableTypes = ['1', '2'];
        $methodIdentifier = 'id';

        $expected = new MethodTypeChangeEvent($availableTypes, $methodIdentifier);
        $factory = new MethodTypeChangeEventFactory();

        static::assertEquals($expected, $factory->create($availableTypes, $methodIdentifier));
    }
}
