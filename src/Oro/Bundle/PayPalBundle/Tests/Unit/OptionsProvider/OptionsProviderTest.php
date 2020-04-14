<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\OptionsProvider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM\Stub\OrderLineItemStub;
use Oro\Bundle\PaymentBundle\Model\AddressOptionModel;
use Oro\Bundle\PaymentBundle\Provider\PaymentOrderShippingAddressOptionsProvider;
use Oro\Bundle\PayPalBundle\OptionsProvider\LineItemOptionsProvider;
use Oro\Bundle\PayPalBundle\OptionsProvider\OptionsProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OptionsProviderTest extends TestCase
{
    /**
     * @var OptionsProvider
     */
    private $provider;

    /**
     * @var PaymentOrderShippingAddressOptionsProvider|MockObject
     */
    private $shippingAddressOptionsProvider;

    /**
     * @var LineItemOptionsProvider|MockObject
     */
    private $lineItemOptionsProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->shippingAddressOptionsProvider = $this->createMock(
            PaymentOrderShippingAddressOptionsProvider::class
        );

        $this->lineItemOptionsProvider = $this->createMock(LineItemOptionsProvider::class);
        $this->provider = new OptionsProvider(
            $this->shippingAddressOptionsProvider,
            $this->lineItemOptionsProvider
        );
    }

    public function testGetShippingAddressOptions(): void
    {
        $address = new OrderAddress();
        $expectedModel = new AddressOptionModel();

        $this->shippingAddressOptionsProvider
            ->expects($this->once())
            ->method('getShippingAddressOptions')
            ->with($address)
            ->willReturn($expectedModel);

        $actualModel = $this->provider->getShippingAddressOptions($address);

        $this->assertSame($expectedModel, $actualModel);
    }

    public function testLineItemOptions(): void
    {
        $expectedLineItemOptions = [new OrderLineItemStub()];
        $order = new Order();

        $this->lineItemOptionsProvider
            ->expects($this->once())
            ->method('getLineItemOptions')
            ->with($order)
            ->willReturn($expectedLineItemOptions);

        $actualLineItemOptions = $this->provider->getLineItemOptions($order);

        $this->assertSame($expectedLineItemOptions, $actualLineItemOptions);
    }
}
