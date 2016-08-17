<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Action;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\CheckoutBundle\Action\DefaultShippingMethodSetter;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Provider\ShippingCostCalculationProvider;
use OroB2B\Bundle\CheckoutBundle\Factory\ShippingContextProviderFactory;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingContextProvider;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingRulesProvider;
use OroB2B\Bundle\ShippingBundle\Tests\Unit\Entity\Stub\CustomShippingRuleConfiguration;

class DefaultShippingMethodSetterTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var DefaultShippingMethodSetter
     */
    protected $setter;

    /**
     * @var ShippingContextProviderFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextProviderFactory;

    /**
     * @var ShippingRulesProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $rulesProvider;

    /**
     * @var ShippingCostCalculationProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $costCalculationProvider;

    public function setUp()
    {
        $this->contextProviderFactory = $this->getMock(ShippingContextProviderFactory::class);
        $this->rulesProvider = $this->getMockBuilder(ShippingRulesProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->costCalculationProvider = $this->getMockBuilder(ShippingCostCalculationProvider::class)
            ->disableOriginalConstructor()->getMock();
        $this->setter = new DefaultShippingMethodSetter(
            $this->contextProviderFactory,
            $this->rulesProvider,
            $this->costCalculationProvider
        );
    }

    public function testSetDefaultShippingMethodAlreadySet()
    {
        $checkout = $this->getEntity(Checkout::class, [
            'shippingMethod' => 'custom_shipping_method'
        ]);
        $this->contextProviderFactory->expects($this->never())
            ->method('create');
        $this->rulesProvider->expects($this->never())
            ->method('getApplicableShippingRules');
        $this->costCalculationProvider->expects($this->never())
            ->method('calculatePrice');
        $this->setter->setDefaultShippingMethod($checkout);
    }

    public function testSetDefaultShippingMethodEmptyApplicable()
    {
        $checkout = $this->getEntity(Checkout::class);
        $context = new ShippingContextProvider([
            'key' => 'value'
        ]);
        $this->contextProviderFactory->expects($this->once())
            ->method('create')
            ->with($checkout)
            ->willReturn($context);
        $this->rulesProvider->expects($this->once())
            ->method('getApplicableShippingRules')
            ->with($context)
            ->willReturn([]);
        $this->costCalculationProvider->expects($this->never())
            ->method('calculatePrice');
        $this->setter->setDefaultShippingMethod($checkout);
    }

    public function testSetDefaultShippingMethod()
    {
        /** @var Checkout $checkout */
        $checkout = $this->getEntity(Checkout::class);
        $context = new ShippingContextProvider([
            'key' => 'value'
        ]);
        $this->contextProviderFactory->expects($this->once())
            ->method('create')
            ->with($checkout)
            ->willReturn($context);

        $method = 'custom_method';
        $methodType = 'custom_method_type';

        $config = $this->getEntity(CustomShippingRuleConfiguration::class, [
            'method' => $method,
            'type' => $methodType,
        ]);
        $rule = $this->getEntity(ShippingRule::class, [
            'configurations' => new ArrayCollection([$config])
        ]);

        $this->rulesProvider->expects($this->once())
            ->method('getApplicableShippingRules')
            ->with($context)
            ->willReturn([$rule]);
        $price = Price::create(10, 'USD');
        $this->costCalculationProvider->expects($this->once())
            ->method('calculatePrice')
            ->with($checkout, $config)
            ->willReturn($price);
        $this->setter->setDefaultShippingMethod($checkout);

        $this->assertEquals($method, $checkout->getShippingMethod());
        $this->assertEquals($methodType, $checkout->getShippingMethodType());
        $this->assertEquals($methodType, $checkout->getShippingMethodType());
        $this->assertSame($price, $checkout->getShippingCost());
    }
}
