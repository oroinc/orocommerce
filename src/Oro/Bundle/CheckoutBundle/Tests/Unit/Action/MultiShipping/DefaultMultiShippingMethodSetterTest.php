<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Action\MultiShipping;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CheckoutBundle\Action\MultiShipping\DefaultMultiShippingMethodSetter;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Manager\MultiShipping\CheckoutLineItemsShippingManager;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\DefaultMultipleShippingMethodProvider;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Method\MultiShippingMethod;
use Oro\Bundle\ShippingBundle\Method\MultiShippingMethodType;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class DefaultMultiShippingMethodSetterTest extends \PHPUnit\Framework\TestCase
{
    /** @var DefaultMultipleShippingMethodProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $multiShippingMethodProvider;

    /** @var CheckoutShippingMethodsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingPriceProvider;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var CheckoutLineItemsShippingManager|\PHPUnit\Framework\MockObject\MockObject */
    private $lineItemsShippingManager;

    /** @var DefaultMultiShippingMethodSetter  */
    private $setter;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->multiShippingMethodProvider = $this->createMock(DefaultMultipleShippingMethodProvider::class);
        $this->shippingPriceProvider = $this->createMock(CheckoutShippingMethodsProviderInterface::class);
        $this->lineItemsShippingManager = $this->createMock(CheckoutLineItemsShippingManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->setter = new DefaultMultiShippingMethodSetter(
            $this->multiShippingMethodProvider,
            $this->shippingPriceProvider,
            $this->doctrine,
            $this->lineItemsShippingManager
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
     * @dataProvider lineItemsShippingMethodsDataProvider
     */
    public function testSetDefaultShippingMethods(?array $lineItemsShippingMethods): void
    {
        $checkout = new Checkout();
        $checkout->setLineItems(new ArrayCollection([new CheckoutLineItem()]));

        $this->multiShippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->willReturn($this->getMultiShippingMethod());

        $this->lineItemsShippingManager->expects(self::once())
            ->method('updateLineItemsShippingMethods')
            ->with($lineItemsShippingMethods, $checkout, true);

        $this->lineItemsShippingManager->expects(self::once())
            ->method('updateLineItemsShippingPrices')
            ->with($checkout);

        $this->shippingPriceProvider->expects(self::once())
            ->method('getPrice')
            ->with($checkout)
            ->willReturn(Price::create(15.0, 'USD'));

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Checkout::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('flush');

        $this->setter->setDefaultShippingMethods($checkout, $lineItemsShippingMethods, true);

        self::assertEquals('multi_shipping', $checkout->getShippingMethod());
        self::assertEquals('multi_shipping_type', $checkout->getShippingMethodType());
        self::assertEquals(15.0, $checkout->getShippingCost()->getValue());
        self::assertEquals('USD', $checkout->getShippingCost()->getCurrency());
    }

    public static function lineItemsShippingMethodsDataProvider(): array
    {
        return [
            [
                [
                    'sku-1:item' => ['method' => 'method1', 'type' => 'type1'],
                    'sku-2:item' => ['identifier' => 'method2', 'type' => 'type2']
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

        $lineItemsShippingMethods = [
            'sku-1:item' => ['method' => 'method1', 'type' => 'type1'],
            'sku-2:item' => ['identifier' => 'method2', 'type' => 'type2']
        ];

        $this->multiShippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->willReturn($this->getMultiShippingMethod());

        $this->lineItemsShippingManager->expects(self::once())
            ->method('updateLineItemsShippingMethods')
            ->with($lineItemsShippingMethods, $checkout, false);

        $this->lineItemsShippingManager->expects(self::once())
            ->method('updateLineItemsShippingPrices')
            ->with($checkout);

        $this->shippingPriceProvider->expects(self::once())
            ->method('getPrice')
            ->with($checkout)
            ->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Checkout::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('flush');

        $this->setter->setDefaultShippingMethods($checkout, $lineItemsShippingMethods);

        self::assertEquals('multi_shipping', $checkout->getShippingMethod());
        self::assertEquals('multi_shipping_type', $checkout->getShippingMethodType());
        self::assertNull($checkout->getShippingCost());
    }
}
