<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Action\MultiShipping;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Action\MultiShipping\DefaultMultiShippingGroupMethodSetter;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Manager\MultiShipping\CheckoutLineItemGroupsShippingManager;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\DefaultMultipleShippingMethodProvider;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Method\MultiShippingMethod;
use Oro\Bundle\ShippingBundle\Method\MultiShippingMethodType;

class DefaultMultiShippingGroupMethodSetterTest extends \PHPUnit\Framework\TestCase
{
    /** @var DefaultMultipleShippingMethodProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $multiShippingMethodProvider;

    /** @var CheckoutShippingMethodsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingPriceProvider;

    /** @var CheckoutLineItemGroupsShippingManager|\PHPUnit\Framework\MockObject\MockObject */
    private $lineItemGroupsShippingManager;

    /** @var DefaultMultiShippingGroupMethodSetter  */
    private $setter;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->multiShippingMethodProvider = $this->createMock(DefaultMultipleShippingMethodProvider::class);
        $this->shippingPriceProvider = $this->createMock(CheckoutShippingMethodsProviderInterface::class);
        $this->lineItemGroupsShippingManager = $this->createMock(CheckoutLineItemGroupsShippingManager::class);

        $this->setter = new DefaultMultiShippingGroupMethodSetter(
            $this->multiShippingMethodProvider,
            $this->shippingPriceProvider,
            $this->lineItemGroupsShippingManager
        );
    }

    private function getMultiShippingMethod(): MultiShippingMethod
    {
        $multiShippingMethodType = $this->createMock(MultiShippingMethodType::class);
        $multiShippingMethodType->expects(self::once())
            ->method('getIdentifier')
            ->willReturn('multi_shipping_type');

        $multiShippingMethod = $this->createMock(MultiShippingMethod::class);
        $multiShippingMethod->expects(self::once())
            ->method('getIdentifier')
            ->willReturn('multi_shipping');
        $multiShippingMethod->expects(self::once())
            ->method('getTypes')
            ->willReturn([$multiShippingMethodType]);

        return $multiShippingMethod;
    }

    /**
     * @dataProvider lineItemGroupsShippingMethodsDataProvider
     */
    public function testSetDefaultShippingMethods(?array $lineItemGroupsShippingMethods): void
    {
        $checkout = new Checkout();
        $checkout->setLineItems(new ArrayCollection([new CheckoutLineItem()]));

        $this->multiShippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->willReturn($this->getMultiShippingMethod());

        $this->lineItemGroupsShippingManager->expects(self::once())
            ->method('updateLineItemGroupsShippingMethods')
            ->with($lineItemGroupsShippingMethods, $checkout, true);

        $this->lineItemGroupsShippingManager->expects(self::once())
            ->method('updateLineItemGroupsShippingPrices')
            ->with($checkout);

        $this->shippingPriceProvider->expects(self::once())
            ->method('getPrice')
            ->with($checkout)
            ->willReturn(Price::create(15.0, 'USD'));

        $this->setter->setDefaultShippingMethods($checkout, $lineItemGroupsShippingMethods, true);

        self::assertEquals('multi_shipping', $checkout->getShippingMethod());
        self::assertEquals('multi_shipping_type', $checkout->getShippingMethodType());
        self::assertEquals(15.0, $checkout->getShippingCost()->getValue());
        self::assertEquals('USD', $checkout->getShippingCost()->getCurrency());
    }

    public static function lineItemGroupsShippingMethodsDataProvider(): array
    {
        return [
            [
                [
                    'product.category:1' => ['method' => 'method1', 'type' => 'type1'],
                    'product.category:2' => ['identifier' => 'method2', 'type' => 'type2']
                ]
            ],
            [[]],
            [null]
        ];
    }

    public function testSetDefaultShippingMethodWithoutShippingCost(): void
    {
        $checkout = new Checkout();
        $checkout->setLineItems(new ArrayCollection([new CheckoutLineItem()]));

        $lineItemGroupsShippingMethods = [
            'product.category:1' => ['method' => 'method1', 'type' => 'type1'],
            'product.category:2' => ['identifier' => 'method2', 'type' => 'type2']
        ];

        $this->multiShippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->willReturn($this->getMultiShippingMethod());

        $this->lineItemGroupsShippingManager->expects(self::once())
            ->method('updateLineItemGroupsShippingMethods')
            ->with($lineItemGroupsShippingMethods, $checkout, false);

        $this->lineItemGroupsShippingManager->expects(self::once())
            ->method('updateLineItemGroupsShippingPrices')
            ->with($checkout);

        $this->shippingPriceProvider->expects(self::once())
            ->method('getPrice')
            ->with($checkout)
            ->willReturn(null);

        $this->setter->setDefaultShippingMethods($checkout, $lineItemGroupsShippingMethods);

        self::assertEquals('multi_shipping', $checkout->getShippingMethod());
        self::assertEquals('multi_shipping_type', $checkout->getShippingMethodType());
        self::assertNull($checkout->getShippingCost());
    }
}
