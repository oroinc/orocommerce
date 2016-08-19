<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\CheckoutBundle\Factory\ShippingContextProviderFactory;
use OroB2B\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingContextProvider;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Provider\ShippingCostCalculationProvider;

class ShippingCostCalculationProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ShippingMethodRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var ShippingCostCalculationProvider
     */
    protected $shippingCostCalculationProvider;

    /**
     * @var ShippingContextProviderFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingContextProviderFactory;

    protected function setUp()
    {
        $this->registry = $this->getMock('OroB2B\Bundle\ShippingBundle\Method\ShippingMethodRegistry');
        $this->shippingContextProviderFactory = $this
            ->getMockBuilder('OroB2B\Bundle\CheckoutBundle\Factory\ShippingContextProviderFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->shippingCostCalculationProvider = new ShippingCostCalculationProvider(
            $this->registry,
            $this->shippingContextProviderFactory
        );
    }

    public function testCalculatePrice()
    {
        /** @var Checkout **/
        $checkout = new Checkout();

        /** @var ShippingRuleConfiguration|\PHPUnit_Framework_MockObject_MockObject $config **/
        $config = $this->getMock('OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration');

        $method = $this->getMock('OroB2B\Bundle\ShippingBundle\Method\ShippingMethodInterface');
        $method->expects($this->once())->method('calculatePrice')->willReturn((new Price()));

        $this->registry->expects($this->once())
            ->method('getShippingMethod')
            ->willReturn($method);

        $this->shippingContextProviderFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn(new ShippingContextProvider([]));

        $actualPrice = $this->shippingCostCalculationProvider->calculatePrice($checkout, $config);
        $this->assertEquals(new Price(), $actualPrice);
    }
}
