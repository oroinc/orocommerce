<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Mapper\MapperInterface;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutSubtotalAmountProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\SubtotalProviderRegistry;

class CheckoutSubtotalAmountProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CheckoutLineItemsManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutLineItemsManager;

    /**
     * @var MapperInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mapper;

    /**
     * @var SubtotalProviderRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subtotalProviderRegistry;

    /**
     * @var CheckoutSubtotalAmountProvider
     */
    private $provider;

    protected function setUp()
    {
        $this->checkoutLineItemsManager = $this->createMock(CheckoutLineItemsManager::class);
        $this->mapper = $this->createMock(MapperInterface::class);
        $this->subtotalProviderRegistry = $this->createMock(SubtotalProviderRegistry::class);
        $this->provider = new CheckoutSubtotalAmountProvider(
            $this->checkoutLineItemsManager,
            $this->mapper,
            $this->subtotalProviderRegistry
        );
    }

    /**
     * @dataProvider getSubtotalAmountProvider
     * @param Order $order
     * @param SubtotalProviderInterface[] $providers
     * @param float $expectedResult
     */
    public function testGetSubtotalAmount(Order $order, array $providers, $expectedResult)
    {
        $checkout = new Checkout();
        $data = new ArrayCollection();
        $this->checkoutLineItemsManager->expects($this->once())
            ->method('getData')
            ->with($checkout)
            ->willReturn($data);
        $this->mapper->expects($this->once())
            ->method('map')
            ->with($checkout, ['lineItems' => $data])
            ->willReturn($order);
        $this->subtotalProviderRegistry->expects($this->once())
            ->method('getSupportedProviders')
            ->with($order)
            ->willReturn($providers);

        $this->assertEquals($expectedResult, $this->provider->getSubtotalAmount($checkout));
    }

    /**
     * @return array
     */
    public function getSubtotalAmountProvider()
    {
        $order = new Order();

        $providerWithWrongType = $this->createMock(SubtotalProviderInterface::class);
        $providerWithWrongType->expects($this->once())
            ->method('getType')
            ->willReturn('some wrong type');
        $providerWithNullSubtotal = $this->createMock(SubtotalProviderInterface::class);
        $providerWithNullSubtotal->expects($this->once())
            ->method('getType')
            ->willReturn(CheckoutSubtotalAmountProvider::SUBTOTAL_PROVIDER_TYPE);
        $providerWithNullSubtotal->expects($this->once())
            ->method('getSubtotal')
            ->with($order)
            ->willReturn(null);

        $subtotalAmount = 100.0;
        $providerWithSubtotal = $this->createMock(SubtotalProviderInterface::class);
        $providerWithSubtotal->expects($this->once())
            ->method('getType')
            ->willReturn(CheckoutSubtotalAmountProvider::SUBTOTAL_PROVIDER_TYPE);
        $providerWithSubtotal->expects($this->once())
            ->method('getSubtotal')
            ->with($order)
            ->willReturn((new Subtotal())->setAmount($subtotalAmount));

        return [
            'when no providers' => [
                'order' => new Order(),
                'providers' => [],
                'expectedResult' => 0.0,
            ],
            'with three different providers' => [
                'order' => $order,
                'providers' => [
                    $providerWithWrongType,
                    $providerWithNullSubtotal,
                    $providerWithSubtotal,
                ],
                'expectedResult' => $subtotalAmount,
            ]
        ];
    }
}
