<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Provider;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingContext;

class ShippingContextTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ShippingContext
     */
    protected $shippingContext;

    protected function setUp()
    {
        $this->shippingContext = new ShippingContext();
    }

    public function testGet()
    {
        $name = 'checkout';
        $checkout = $this->shippingContext->get($name);
        $this->assertTrue($checkout instanceof Checkout);
    }
}
