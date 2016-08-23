<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CheckoutBundle\Factory\ShippingContextProviderFactory;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration;
use Oro\Bundle\ShippingBundle\Provider\ShippingContextProvider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\ShippingCostCalculationProvider;

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
        $this->registry = $this->getMock('Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry');
        $this->shippingContextProviderFactory = $this
            ->getMockBuilder('Oro\Bundle\CheckoutBundle\Factory\ShippingContextProviderFactory')
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
        $config = $this->getMock('Oro\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration');

        $method = $this->getMock('Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface');
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
