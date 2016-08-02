<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Provider;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingContextProvider;

class ShippingContextProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingContext;

    /**
     * @var ShippingContextProvider
     */
    protected $shippingContextProvider;

    protected function setUp()
    {
        $this->shippingContext = ['checkout' => new Checkout()];
        $this->shippingContextProvider = new ShippingContextProvider($this->shippingContext);
    }

    public function testGetShippingContext()
    {
        $context = $this->shippingContextProvider->getShippingContext();
        $this->assertTrue(is_array($context));
        $this->assertTrue(array_key_exists('checkout', $context));
        $this->assertTrue($context['checkout'] instanceof Checkout);
    }
}
