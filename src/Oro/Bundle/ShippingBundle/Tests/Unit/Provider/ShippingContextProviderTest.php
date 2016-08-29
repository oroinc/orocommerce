<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider;

use Oro\Bundle\ShippingBundle\Provider\ShippingContextProvider;

class ShippingContextProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetShippingContext()
    {
        $context = ['currency' => 'USD'];
        $shippingContextProvider = new ShippingContextProvider($context);
        $this->assertEquals($context, $shippingContextProvider->getShippingContext());
    }
}
