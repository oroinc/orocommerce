<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider\AddressValidation;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\AddressValidation\CheckoutAddressFormShippingAddressProvider;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;

class CheckoutAddressFormShippingAddressProviderTest extends TestCase
{
    private CheckoutAddressFormShippingAddressProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new CheckoutAddressFormShippingAddressProvider();
    }

    public function testResolveReturnsAddressDataWhenRootFormHasNoShipToBillingAddress(): void
    {
        $address = new OrderAddress();
        $addressForm = $this->createMock(FormInterface::class);
        $rootForm = $this->createMock(FormInterface::class);

        $addressForm
            ->expects(self::once())
            ->method('getRoot')
            ->willReturn($rootForm);

        $rootForm
            ->expects(self::once())
            ->method('has')
            ->with('ship_to_billing_address')
            ->willReturn(false);

        $addressForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn($address);

        self::assertSame($address, $this->provider->getAddress($addressForm));
    }

    public function testResolveReturnsBillingAddressWhenShipToBillingAddressIsChecked(): void
    {
        $billingAddress = new OrderAddress();
        $checkout = (new Checkout())
            ->setBillingAddress($billingAddress);

        $addressForm = $this->createMock(FormInterface::class);
        $rootForm = $this->createMock(FormInterface::class);
        $addressForm
            ->expects(self::once())
            ->method('getRoot')
            ->willReturn($rootForm);

        $rootForm
            ->expects(self::once())
            ->method('has')
            ->with('ship_to_billing_address')
            ->willReturn(true);

        $shipToBillingAddressField = $this->createMock(FormInterface::class);
        $rootForm
            ->expects(self::once())
            ->method('get')
            ->with('ship_to_billing_address')
            ->willReturn($shipToBillingAddressField);

        $shipToBillingAddressField
            ->expects(self::once())
            ->method('getData')
            ->willReturn(true);

        $addressFormConfig = $this->createMock(FormConfigInterface::class);
        $addressForm
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($addressFormConfig);

        $addressFormConfig
            ->expects(self::once())
            ->method('getOption')
            ->with('object')
            ->willReturn($checkout);

        self::assertSame($billingAddress, $this->provider->getAddress($addressForm));
    }

    public function testResolveReturnsAddressDataWhenShipToBillingAddressIsNotChecked(): void
    {
        $address = new OrderAddress();
        $addressForm = $this->createMock(FormInterface::class);
        $rootForm = $this->createMock(FormInterface::class);

        $addressForm
            ->expects(self::once())
            ->method('getRoot')
            ->willReturn($rootForm);

        $rootForm
            ->expects(self::once())
            ->method('has')
            ->with('ship_to_billing_address')
            ->willReturn(true);

        $shipToBillingAddressField = $this->createMock(FormInterface::class);
        $rootForm
            ->expects(self::once())
            ->method('get')
            ->with('ship_to_billing_address')
            ->willReturn($shipToBillingAddressField);

        $shipToBillingAddressField
            ->expects(self::once())
            ->method('getData')
            ->willReturn(false);

        $addressForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn($address);

        self::assertSame($address, $this->provider->getAddress($addressForm));
    }

    public function testResolveReturnsNullWhenAddressFormDataIsNull(): void
    {
        $addressForm = $this->createMock(FormInterface::class);
        $rootForm = $this->createMock(FormInterface::class);

        $addressForm
            ->expects(self::once())
            ->method('getRoot')
            ->willReturn($rootForm);

        $rootForm
            ->expects(self::once())
            ->method('has')
            ->with('ship_to_billing_address')
            ->willReturn(false);

        $addressForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn(null);

        self::assertNull($this->provider->getAddress($addressForm));
    }
}
