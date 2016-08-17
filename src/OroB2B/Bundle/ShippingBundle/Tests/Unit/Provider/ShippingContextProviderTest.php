<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Provider;

use OroB2B\Bundle\ShippingBundle\Provider\ShippingContextProvider;

class ShippingContextProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetShippingContext()
    {
        $context = ['currency' => 'USD'];
        $shippingContextProvider = new ShippingContextProvider($context);
        $this->assertEquals($context, $shippingContextProvider->getShippingContext());
    }
}
