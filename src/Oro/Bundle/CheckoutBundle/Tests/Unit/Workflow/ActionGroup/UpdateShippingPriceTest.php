<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\ActionGroup;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\UpdateShippingPrice;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateShippingPriceTest extends TestCase
{
    private CheckoutShippingMethodsProviderInterface|MockObject $checkoutShippingMethodsProvider;

    private UpdateShippingPrice $updateShippingPrice;

    protected function setUp(): void
    {
        $this->checkoutShippingMethodsProvider = $this->createMock(CheckoutShippingMethodsProviderInterface::class);
        $this->updateShippingPrice = new UpdateShippingPrice($this->checkoutShippingMethodsProvider);
    }

    public function testExecute(): void
    {
        $checkout = new Checkout();
        $shippingPrice = Price::create(100.0, 'USD');

        $this->checkoutShippingMethodsProvider
            ->expects($this->once())
            ->method('getPrice')
            ->with($checkout)
            ->willReturn($shippingPrice);

        $this->updateShippingPrice->execute($checkout);

        $this->assertEquals($shippingPrice, $checkout->getShippingCost());
    }
}
