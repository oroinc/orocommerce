<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\ActionGroup;

use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\AddressActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\PrepareCheckoutSettings;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PrepareCheckoutSettingsTest extends TestCase
{
    private AddressActionsInterface|MockObject $addressActions;
    private PaymentTransactionProvider|MockObject $paymentTransactionProvider;
    private PrepareCheckoutSettings $prepareCheckoutSettings;

    #[\Override]
    protected function setUp(): void
    {
        $this->addressActions = $this->createMock(AddressActionsInterface::class);
        $this->paymentTransactionProvider = $this->createMock(PaymentTransactionProvider::class);

        $this->prepareCheckoutSettings = new PrepareCheckoutSettings(
            $this->addressActions,
            $this->paymentTransactionProvider
        );
    }

    public function testExecuteWithFullSource()
    {
        $source = $this->createMock(Order::class);
        $billingAddress = new OrderAddress();
        $billingAddressCopy = new OrderAddress();
        $shippingAddress = new OrderAddress();
        $shippingAddressCopy = new OrderAddress();
        $paymentMethods = ['payment_method_1'];

        $source->expects($this->any())
            ->method('getBillingAddress')
            ->willReturn($billingAddress);
        $source->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($shippingAddress);
        $source->expects($this->any())
            ->method('getShippingMethod')
            ->willReturn('shipping_method');
        $source->expects($this->any())
            ->method('getShippingMethodType')
            ->willReturn('shipping_method_type');

        $this->addressActions->expects($this->exactly(2))
            ->method('duplicateOrderAddress')
            ->withConsecutive([$billingAddress], [$shippingAddress])
            ->willReturnOnConsecutiveCalls($billingAddressCopy, $shippingAddressCopy);

        $this->paymentTransactionProvider->expects($this->once())
            ->method('getPaymentMethods')
            ->with($source)
            ->willReturn($paymentMethods);

        $settings = $this->prepareCheckoutSettings->execute($source);

        $this->assertEquals([
            'billing_address' => $billingAddressCopy,
            'shipping_address' => $shippingAddressCopy,
            'shipping_method' => 'shipping_method',
            'shipping_method_type' => 'shipping_method_type',
            'payment_method' => 'payment_method_1',
        ], $settings);
    }

    public function testExecuteWithPartialSource()
    {
        $source = $this->createMock(Order::class);
        $billingAddress = new OrderAddress();
        $billingAddressCopy = new OrderAddress();
        $paymentMethods = ['payment_method_1'];

        $source->expects($this->any())
            ->method('getBillingAddress')
            ->willReturn($billingAddress);
        $source->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn(null);

        $this->addressActions->expects($this->once())
            ->method('duplicateOrderAddress')
            ->with($billingAddress)
            ->willReturn($billingAddressCopy);

        $this->paymentTransactionProvider->expects($this->once())
            ->method('getPaymentMethods')
            ->with($source)
            ->willReturn($paymentMethods);

        $settings = $this->prepareCheckoutSettings->execute($source);

        $this->assertEquals([
            'billing_address' => $billingAddressCopy,
            'payment_method' => 'payment_method_1',
        ], $settings);
    }

    public function testExecuteWithEmptySource()
    {
        $source = $this->createMock(CheckoutSourceEntityInterface::class);

        $this->addressActions->expects($this->never())
            ->method('duplicateOrderAddress');

        $this->paymentTransactionProvider->expects($this->once())
            ->method('getPaymentMethods')
            ->with($source)
            ->willReturn([]);

        $settings = $this->prepareCheckoutSettings->execute($source);

        $this->assertEquals([], $settings);
    }
}
