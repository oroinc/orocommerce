<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Action;

use Oro\Bundle\CheckoutBundle\Action\DefaultShippingMethodSetter;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Component\Testing\Unit\EntityTrait;

class DefaultShippingMethodSetterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var CheckoutShippingMethodsProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $checkoutShippingMethodsProviderMock;

    /**
     * @var DefaultShippingMethodSetter
     */
    private $defaultShippingMethodSetter;

    protected function setUp(): void
    {
        $this->checkoutShippingMethodsProviderMock = $this
            ->getMockBuilder(CheckoutShippingMethodsProviderInterface::class)
            ->getMock();

        $this->defaultShippingMethodSetter = new DefaultShippingMethodSetter(
            $this->checkoutShippingMethodsProviderMock
        );

        parent::setUp();
    }

    public function testSetDefaultShippingMethodAlreadySet()
    {
        $checkout = $this->getEntity(
            Checkout::class,
            [
                'shippingMethod' => 'custom_shipping_method',
            ]
        );
        $this->checkoutShippingMethodsProviderMock->expects($this->never())
            ->method('getApplicableMethodsViews');

        $this->defaultShippingMethodSetter->setDefaultShippingMethod($checkout);
    }

    public function testSetDefaultShippingMethodEmptyApplicable()
    {
        $checkout = $this->getEntity(Checkout::class);

        $this->checkoutShippingMethodsProviderMock->expects($this->once())
            ->method('getApplicableMethodsViews')
            ->with($checkout)
            ->willReturn(new ShippingMethodViewCollection());

        $this->defaultShippingMethodSetter->setDefaultShippingMethod($checkout);
    }

    public function testSetDefaultShippingMethod()
    {
        /** @var Checkout $checkout */
        $checkout = $this->getEntity(Checkout::class);

        $method = 'custom_method';
        $methodType = 'custom_method_type';

        $price = Price::create(10, 'USD');
        $this->checkoutShippingMethodsProviderMock->expects($this->once())
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
