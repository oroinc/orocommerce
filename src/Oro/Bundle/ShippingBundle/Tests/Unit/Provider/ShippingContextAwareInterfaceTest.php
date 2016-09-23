<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider;

use Oro\Bundle\ShippingBundle\Provider\ShippingContextAwareInterface;

class ShippingContextAwareInterfaceTest extends \PHPUnit_Framework_TestCase
{
    public function testGetShippingContext()
    {
        $this->assertTrue(
            method_exists(ShippingContextAwareInterface::class, 'getShippingContext'),
            'Class ShippingContextAwareInterface does not have method getShippingContext'
        );
    }
}
