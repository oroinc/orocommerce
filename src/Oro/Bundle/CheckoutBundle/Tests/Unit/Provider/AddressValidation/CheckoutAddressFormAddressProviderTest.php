<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider\AddressValidation;

use Oro\Bundle\CheckoutBundle\Provider\AddressValidation\CheckoutAddressFormAddressProvider;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;

class CheckoutAddressFormAddressProviderTest extends TestCase
{
    protected function setUp(): void
    {
        $this->provider = new CheckoutAddressFormAddressProvider();
    }

    public function testGetAddress(): void
    {
        $addressForm = $this->createMock(FormInterface::class);
        $orderAddress = new OrderAddress();
        $addressForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn($orderAddress);

        self::assertSame($orderAddress, $this->provider->getAddress($addressForm));
    }
}
