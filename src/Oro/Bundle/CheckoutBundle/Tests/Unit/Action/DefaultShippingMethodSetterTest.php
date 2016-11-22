<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Action;

use Oro\Bundle\CheckoutBundle\Action\DefaultShippingMethodSetter;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\ShippingContextProviderFactory;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;
use Oro\Component\Testing\Unit\EntityTrait;

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
     * @var ShippingPriceProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceProvider;

    public function setUp()
    {
        $this->contextProviderFactory = $this->getMockBuilder(ShippingContextProviderFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceProvider = $this->getMockBuilder(ShippingPriceProvider::class)
            ->disableOriginalConstructor()->getMock();
        $this->setter = new DefaultShippingMethodSetter(
            $this->contextProviderFactory,
            $this->priceProvider,
            $this->priceProvider
        );
    }

    public function testSetDefaultShippingMethodAlreadySet()
    {
        $checkout = $this->getEntity(Checkout::class, [
            'shippingMethod' => 'custom_shipping_method'
        ]);
        $this->contextProviderFactory->expects($this->never())
            ->method('create');
        $this->priceProvider->expects($this->never())
            ->method('getApplicableMethodsWithTypesData');
        $this->priceProvider->expects($this->never())
            ->method('getPrice');
        $this->setter->setDefaultShippingMethod($checkout);
    }

    public function testSetDefaultShippingMethodEmptyApplicable()
    {
        $checkout = $this->getEntity(Checkout::class);
        $context = new ShippingContext();
        $this->contextProviderFactory->expects($this->once())
            ->method('create')
            ->with($checkout)
            ->willReturn($context);
        $this->priceProvider->expects($this->once())
            ->method('getApplicableMethodsWithTypesData')
            ->with($context)
            ->willReturn([]);
        $this->priceProvider->expects($this->never())
            ->method('getPrice');
        $this->setter->setDefaultShippingMethod($checkout);
    }

    public function testSetDefaultShippingMethod()
    {
        /** @var Checkout $checkout */
        $checkout = $this->getEntity(Checkout::class);
        $context = new ShippingContext();
        $this->contextProviderFactory->expects($this->once())
            ->method('create')
            ->with($checkout)
            ->willReturn($context);

        $method = 'custom_method';
        $methodType = 'custom_method_type';

        $price = Price::create(10, 'USD');
        $this->priceProvider->expects($this->once())
            ->method('getApplicableMethodsWithTypesData')
            ->with($context)
            ->willReturn([
                [
                    'identifier' => $method,
                    'types' => [
                        [
                            'identifier' => $methodType,
                            'price' => $price,
                        ]
                    ],
                ]
            ]);

        $this->setter->setDefaultShippingMethod($checkout);
        $this->assertEquals($method, $checkout->getShippingMethod());
        $this->assertEquals($methodType, $checkout->getShippingMethodType());
        $this->assertEquals($methodType, $checkout->getShippingMethodType());
        $this->assertSame($price, $checkout->getShippingCost());
    }
}
