<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItem\LineItemShippingPriceProviderInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Provider\MultiShippingCostProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;

class MultiShippingCostProviderTest extends TestCase
{
    use EntityTrait;

    private LineItemShippingPriceProviderInterface $lineItemShippingPriceProvider;
    private MultiShippingCostProvider $costProvider;

    protected function setUp(): void
    {
        $this->lineItemShippingPriceProvider = $this->createMock(LineItemShippingPriceProviderInterface::class);
        $this->costProvider = new MultiShippingCostProvider($this->lineItemShippingPriceProvider);
    }

    public function testGetCalculatedMultiShippingCost()
    {
        $lineItem1 = $this->getEntity(CheckoutLineItem::class, [
            'shippingMethod' => 'flat_rate_1',
            'shippingMethodType' => 'primary'
        ]);

        $lineItem2 = $this->getEntity(CheckoutLineItem::class, [
            'shippingMethod' => 'flat_rate_1',
            'shippingMethodType' => 'primary',
            'currency' => 'USD',
            'shippingEstimateAmount' => 5.10
        ]);

        $lineItem3 = $this->getEntity(CheckoutLineItem::class, [
            'shippingMethod' => 'flat_rate_2',
            'shippingMethodType' => 'primary',
        ]);

        $checkout = new Checkout();
        $checkout->setLineItems(new ArrayCollection([$lineItem1, $lineItem2, $lineItem3]));

        $this->lineItemShippingPriceProvider->expects($this->exactly(2))
            ->method('getPrice')
            ->willReturnOnConsecutiveCalls(Price::create(7.05, 'USD'), Price::create(18.46, 'USD'));

        $result = $this->costProvider->getCalculatedMultiShippingCost($checkout);

        $this->assertEquals(30.61, $result);
    }
}
