<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItem\LineItemShippingPriceProviderInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Provider\MultiShippingCostProvider;

class MultiShippingCostProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var LineItemShippingPriceProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $lineItemShippingPriceProvider;

    /** @var MultiShippingCostProvider */
    private $costProvider;

    protected function setUp(): void
    {
        $this->lineItemShippingPriceProvider = $this->createMock(LineItemShippingPriceProviderInterface::class);

        $this->costProvider = new MultiShippingCostProvider($this->lineItemShippingPriceProvider);
    }

    private function getCheckout(array $lineItems): Checkout
    {
        $checkout = new Checkout();
        $checkout->setLineItems(new ArrayCollection($lineItems));

        return $checkout;
    }

    private function getCheckoutLineItem(
        string $shippingMethod,
        string $shippingMethodType,
        ?string $currency = null,
        ?float $shippingEstimateAmount = null
    ): CheckoutLineItem {
        $lineItem = new CheckoutLineItem();
        $lineItem->setShippingMethod($shippingMethod);
        $lineItem->setShippingMethodType($shippingMethodType);
        if (null !== $currency) {
            $lineItem->setCurrency($currency);
        }
        if (null !== $shippingEstimateAmount) {
            $lineItem->setShippingEstimateAmount($shippingEstimateAmount);
        }

        return $lineItem;
    }

    public function testGetCalculatedMultiShippingCost()
    {
        $checkout = $this->getCheckout([
            $this->getCheckoutLineItem('flat_rate_1', 'primary'),
            $this->getCheckoutLineItem('flat_rate_1', 'primary', 'USD', 5.10),
            $this->getCheckoutLineItem('flat_rate_2', 'primary')
        ]);

        $this->lineItemShippingPriceProvider->expects($this->exactly(2))
            ->method('getPrice')
            ->willReturnOnConsecutiveCalls(Price::create(7.05, 'USD'), Price::create(18.46, 'USD'));

        $result = $this->costProvider->getCalculatedMultiShippingCost($checkout);

        $this->assertEquals(30.61, $result);
    }
}
