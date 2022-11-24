<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Action;

use Oro\Bundle\CheckoutBundle\Action\DefaultShippingMethodSetter;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;

class DefaultShippingMethodSetterTest extends \PHPUnit\Framework\TestCase
{
    /** @var CheckoutShippingMethodsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutShippingMethodsProvider;

    /** @var DefaultShippingMethodSetter */
    private $defaultShippingMethodSetter;

    protected function setUp(): void
    {
        $this->checkoutShippingMethodsProvider = $this->createMock(CheckoutShippingMethodsProviderInterface::class);

        $this->defaultShippingMethodSetter = new DefaultShippingMethodSetter(
            $this->checkoutShippingMethodsProvider
        );

        parent::setUp();
    }

    public function testSetDefaultShippingMethodAlreadySet()
    {
        $checkout = new Checkout();
        $checkout->setShippingMethod('custom_shipping_method');

        $this->checkoutShippingMethodsProvider->expects($this->never())
            ->method('getApplicableMethodsViews');

        $this->defaultShippingMethodSetter->setDefaultShippingMethod($checkout);
    }

    public function testSetDefaultShippingMethodEmptyApplicable()
    {
        $checkout = new Checkout();

        $this->checkoutShippingMethodsProvider->expects($this->once())
            ->method('getApplicableMethodsViews')
            ->with($checkout)
            ->willReturn(new ShippingMethodViewCollection());

        $this->defaultShippingMethodSetter->setDefaultShippingMethod($checkout);
    }

    public function testSetDefaultShippingMethod()
    {
        $checkout = new Checkout();

        $method = 'custom_method';
        $methodType = 'custom_method_type';

        $price = Price::create(10, 'USD');
        $this->checkoutShippingMethodsProvider->expects($this->once())
            ->method('getApplicableMethodsViews')
            ->with($checkout)
            ->willReturn(
                (new ShippingMethodViewCollection())
                    ->addMethodView($method, ['identifier' => $method])
                    ->addMethodTypeView(
                        $method,
                        $methodType,
                        ['identifier' => $methodType, 'price' => $price]
                    )
            );

        $this->defaultShippingMethodSetter->setDefaultShippingMethod($checkout);
        $this->assertEquals($method, $checkout->getShippingMethod());
        $this->assertEquals($methodType, $checkout->getShippingMethodType());
        $this->assertEquals($methodType, $checkout->getShippingMethodType());
        $this->assertSame($price, $checkout->getShippingCost());
    }
}
