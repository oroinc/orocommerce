<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Action\MultiShipping;

use Oro\Bundle\CheckoutBundle\Action\MultiShipping\SubOrderMultiShippingMethodSetter;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\CurrencyBundle\Entity\Price;

class SubOrderMultiShippingMethodSetterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var SubOrderMultiShippingMethodSetter */
    private $setter;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->setter = new SubOrderMultiShippingMethodSetter($this->configProvider);
    }

    public function testWhenShippingSelectionByLineItemEnabledAndNoShippingDataInChildCheckout(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $childCheckout = $this->createMock(Checkout::class);
        $lineItemGroupKey = 'product.category:1';

        $this->configProvider->expects(self::once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(true);

        $checkout->expects(self::never())
            ->method(self::anything());

        $childCheckout->expects(self::once())
            ->method('getLineItemGroupShippingData')
            ->willReturn([]);
        $childCheckout->expects(self::never())
            ->method('setLineItemGroupShippingData');

        $this->setter->setShippingMethod($checkout, $childCheckout, $lineItemGroupKey);
    }

    public function testWhenShippingSelectionByLineItemEnabledAndChildCheckoutHasShippingData(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $childCheckout = $this->createMock(Checkout::class);
        $lineItemGroupKey = 'product.category:1';

        $this->configProvider->expects(self::once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(true);

        $checkout->expects(self::never())
            ->method(self::anything());

        $childCheckout->expects(self::once())
            ->method('getLineItemGroupShippingData')
            ->willReturn(['key' => 'val']);
        $childCheckout->expects(self::once())
            ->method('setLineItemGroupShippingData')
            ->with([]);

        $this->setter->setShippingMethod($checkout, $childCheckout, $lineItemGroupKey);
    }

    public function testWhenShippingSelectionByLineItemDisabledAndNoShippingDataInCheckout(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $childCheckout = $this->createMock(Checkout::class);
        $lineItemGroupKey = 'product.category:1';

        $this->configProvider->expects(self::once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(false);

        $checkout->expects(self::once())
            ->method('getLineItemGroupShippingData')
            ->willReturn([]);

        $childCheckout->expects(self::never())
            ->method(self::anything());

        $this->setter->setShippingMethod($checkout, $childCheckout, $lineItemGroupKey);
    }

    public function testWhenShippingSelectionByLineItemDisabledAndCheckoutHasShippingData(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $childCheckout = $this->createMock(Checkout::class);
        $lineItemGroupKey = 'product.category:1';

        $this->configProvider->expects(self::once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(false);

        $checkout->expects(self::once())
            ->method('getLineItemGroupShippingData')
            ->willReturn([$lineItemGroupKey => ['method' => 'method1', 'type' => 'type1']]);

        $childCheckout->expects(self::once())
            ->method('setShippingMethod')
            ->with('method1');
        $childCheckout->expects(self::once())
            ->method('setShippingMethodType')
            ->with('type1');
        $childCheckout->expects(self::never())
            ->method('getCurrency');
        $childCheckout->expects(self::once())
            ->method('setShippingCost')
            ->with(null);

        $this->setter->setShippingMethod($checkout, $childCheckout, $lineItemGroupKey);
    }

    public function testWhenShippingSelectionByLineItemDisabledAndCheckoutHasShippingDataAndAmount(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $childCheckout = $this->createMock(Checkout::class);
        $lineItemGroupKey = 'product.category:1';

        $this->configProvider->expects(self::once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(false);

        $checkout->expects(self::once())
            ->method('getLineItemGroupShippingData')
            ->willReturn([$lineItemGroupKey => ['method' => 'method1', 'type' => 'type1', 'amount' => 1.1]]);

        $childCheckout->expects(self::once())
            ->method('setShippingMethod')
            ->with('method1');
        $childCheckout->expects(self::once())
            ->method('setShippingMethodType')
            ->with('type1');
        $childCheckout->expects(self::once())
            ->method('getCurrency')
            ->willReturn('USD');
        $childCheckout->expects(self::once())
            ->method('setShippingCost')
            ->with(Price::create(1.1, 'USD'));

        $this->setter->setShippingMethod($checkout, $childCheckout, $lineItemGroupKey);
    }

    public function testWhenShippingSelectionByLineItemDisabledAndCheckoutHasOnlyAmount(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $childCheckout = $this->createMock(Checkout::class);
        $lineItemGroupKey = 'product.category:1';

        $this->configProvider->expects(self::once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(false);

        $checkout->expects(self::once())
            ->method('getLineItemGroupShippingData')
            ->willReturn([$lineItemGroupKey => ['amount' => 1.1]]);

        $childCheckout->expects(self::never())
            ->method('setShippingMethod');
        $childCheckout->expects(self::never())
            ->method('setShippingMethodType');
        $childCheckout->expects(self::never())
            ->method('setShippingCost');

        $this->setter->setShippingMethod($checkout, $childCheckout, $lineItemGroupKey);
    }
}
