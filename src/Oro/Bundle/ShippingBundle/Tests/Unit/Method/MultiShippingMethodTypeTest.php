<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Method\MultiShippingMethodType;
use Oro\Bundle\ShippingBundle\Provider\MultiShippingCostProvider;

class MultiShippingMethodTypeTest extends \PHPUnit\Framework\TestCase
{
    private const LABEL = 'Multi Shipping Label';

    /** @var RoundingServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $roundingService;

    /** @var MultiShippingCostProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingCostProvider;

    /** @var MultiShippingMethodType */
    private $methodType;

    protected function setUp(): void
    {
        $this->roundingService = $this->createMock(RoundingServiceInterface::class);
        $this->shippingCostProvider = $this->createMock(MultiShippingCostProvider::class);

        $this->methodType = new MultiShippingMethodType(
            self::LABEL,
            $this->roundingService,
            $this->shippingCostProvider
        );
    }

    public function testGetIdentifier()
    {
        $this->assertEquals('primary', $this->methodType->getIdentifier());
    }

    public function testGetLabel()
    {
        $this->assertEquals(self::LABEL, $this->methodType->getLabel());
    }

    public function testGetSortOrder()
    {
        $this->assertEquals(0, $this->methodType->getSortOrder());
    }

    public function testGetOptionsConfigurationFormType()
    {
        $this->assertNull($this->methodType->getOptionsConfigurationFormType());
    }

    public function testCalculatePrice()
    {
        $context = new ShippingContext([
            ShippingContext::FIELD_SOURCE_ENTITY => new Checkout(),
            ShippingContext::FIELD_CURRENCY => 'USD'
        ]);

        $this->shippingCostProvider->expects($this->once())
            ->method('getCalculatedMultiShippingCost')
            ->willReturn(9.69999);

        $this->roundingService->expects($this->once())
            ->method('round')
            ->with(9.69999)
            ->willReturn(9.70);

        $result = $this->methodType->calculatePrice($context, [], []);

        $this->assertInstanceOf(Price::class, $result);
        $this->assertEquals(9.70, $result->getValue());
        $this->assertEquals('USD', $result->getCurrency());
    }

    public function testCalculatePriceIfSourceEntityIsNotCheckout()
    {
        $context = new ShippingContext([
            ShippingContext::FIELD_SOURCE_ENTITY => new Order(),
            ShippingContext::FIELD_CURRENCY => 'USD'
        ]);

        $this->shippingCostProvider->expects($this->never())
            ->method('getCalculatedMultiShippingCost');

        $this->roundingService->expects($this->never())
            ->method('round');

        $result = $this->methodType->calculatePrice($context, [], []);
        $this->assertNull($result);
    }
}
