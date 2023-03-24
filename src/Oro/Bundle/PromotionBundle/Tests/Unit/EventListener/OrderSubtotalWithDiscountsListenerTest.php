<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Tests\Unit\EventListener\Order\SubtotalTrait;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PromotionBundle\EventListener\OrderSubtotalWithDiscountsListener;
use Oro\Bundle\PromotionBundle\Provider\SubtotalProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrderSubtotalWithDiscountsListenerTest extends TestCase
{
    use SubtotalTrait;
    use EntityTrait;

    private SubtotalProviderInterface|MockObject $subtotalProvider;
    private OrderSubtotalWithDiscountsListener $subtotalWithDiscountsListener;

    protected function setUp(): void
    {
        $this->subtotalProvider = $this->createMock(SubtotalProviderInterface::class);
        $this->subtotalWithDiscountsListener = new OrderSubtotalWithDiscountsListener($this->subtotalProvider);
    }

    /**
     * @dataProvider getSubtotalsForSupportedEntity
     */
    public function testPrePersistPreUpdateWithSupportedEntity(
        array $subtotals,
        float $amount,
        ArrayCollection $discounts,
        float $result
    ): void {
        $order = $this->getEntity(Order::class, ['subtotal' => $amount, 'discounts' => $discounts]);

        $preUpdateEvent = $this
            ->getMockBuilder(PreUpdateEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();
        $prePersistEvent = $this
            ->getMockBuilder(LifecycleEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this
            ->subtotalProvider
            ->expects(self::atLeastOnce())
            ->method('getSubtotal')
            ->with($order)
            ->willReturn($subtotals);
        $this
            ->subtotalProvider
            ->expects(self::atLeastOnce())
            ->method('isSupported')
            ->with($order)
            ->willReturn(true);

        $this->subtotalWithDiscountsListener->prePersist($order, $prePersistEvent);
        self::assertEquals($order->getSubtotalWithDiscounts(), $result);
        $this->subtotalWithDiscountsListener->preUpdate($order, $preUpdateEvent);
        self::assertEquals($order->getSubtotalWithDiscounts(), $result);
    }

    /**
     * @dataProvider getSubtotalsForUnsupportedEntity
     */
    public function testPrePersistPreUpdateWithUnsupportedEntity(
        array $subtotals,
        float $amount,
        ArrayCollection $discounts,
        float $result
    ): void {
        $order = $this->getEntity(Order::class, ['subtotal' => $amount, 'discounts' => $discounts]);

        $preUpdateEvent = $this
            ->getMockBuilder(PreUpdateEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();
        $prePersistEvent = $this
            ->getMockBuilder(LifecycleEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this
            ->subtotalProvider
            ->expects(self::never())
            ->method('getSubtotal')
            ->with($order)
            ->willReturn($subtotals);
        $this
            ->subtotalProvider
            ->expects(self::atLeastOnce())
            ->method('isSupported')
            ->with($order)
            ->willReturn(false);

        $this->subtotalWithDiscountsListener->prePersist($order, $prePersistEvent);
        self::assertEquals($order->getSubtotalWithDiscounts(), $result);
        $this->subtotalWithDiscountsListener->preUpdate($order, $preUpdateEvent);
        self::assertEquals($order->getSubtotalWithDiscounts(), $result);
    }

    private function getSubtotalsForSupportedEntity(): array
    {
        return [
            'operation add' => [
                'subtotals' => [
                    SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL => $this->getSubtotal(
                        SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL,
                        SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL,
                        10.00,
                        '$',
                        true
                    )->setOperation(Subtotal::OPERATION_ADD),
                    SubtotalProvider::ORDER_DISCOUNT_SUBTOTAL => $this->getSubtotal(
                        SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL,
                        SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL,
                        20.00,
                        '$',
                        true
                    )->setOperation(Subtotal::OPERATION_ADD),
                ],
                'amount' => 30.00,
                'discounts' => new ArrayCollection([
                    $this->getEntity(OrderDiscount::class, ['amount' => 5.00]),
                ]),
                'result' => 45.00,
            ],
            'operation subtract' => [
                'subtotals' => [
                    SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL => $this->getSubtotal(
                        SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL,
                        SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL,
                        10.00,
                        '$',
                        true
                    )->setOperation(Subtotal::OPERATION_SUBTRACTION),
                    SubtotalProvider::ORDER_DISCOUNT_SUBTOTAL => $this->getSubtotal(
                        SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL,
                        SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL,
                        10.00,
                        '$',
                        true
                    )->setOperation(Subtotal::OPERATION_SUBTRACTION),
                ],
                'amount' => 20.00,
                'discounts' => new ArrayCollection([
                    $this->getEntity(OrderDiscount::class, ['amount' => 1.00]),
                    $this->getEntity(OrderDiscount::class, ['amount' => 2.00]),
                    $this->getEntity(OrderDiscount::class, ['amount' => 2.00]),
                ]),
                'result' => 5.00,
            ],
            'operation subtract with negative result' => [
                'subtotals' => [
                    SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL => $this->getSubtotal(
                        SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL,
                        SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL,
                        10.00,
                        '$',
                        true
                    )->setOperation(Subtotal::OPERATION_SUBTRACTION),
                    SubtotalProvider::ORDER_DISCOUNT_SUBTOTAL => $this->getSubtotal(
                        SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL,
                        SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL,
                        20.00,
                        '$',
                        true
                    )->setOperation(Subtotal::OPERATION_SUBTRACTION),
                ],
                'amount' => 10.00,
                'discounts' => new ArrayCollection([
                    $this->getEntity(OrderDiscount::class, ['amount' => 10.00]),
                    $this->getEntity(OrderDiscount::class, ['amount' => 20.00]),
                    $this->getEntity(OrderDiscount::class, ['amount' => 30.00]),
                ]),
                'result' => 0.00,
            ],
            'operation subtract with negative result and empty discounts' => [
                'subtotals' => [
                    SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL => $this->getSubtotal(
                        SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL,
                        SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL,
                        10.00,
                        '$',
                        true
                    )->setOperation(Subtotal::OPERATION_SUBTRACTION),
                    SubtotalProvider::ORDER_DISCOUNT_SUBTOTAL => $this->getSubtotal(
                        SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL,
                        SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL,
                        20.00,
                        '$',
                        true
                    )->setOperation(Subtotal::OPERATION_SUBTRACTION),
                ],
                'amount' => 10.00,
                'discounts' => new ArrayCollection([]),
                'result' => 0.00,
            ]
        ];
    }

    private function getSubtotalsForUnsupportedEntity(): array
    {
        return [
            'operation add and unsupported entity' => [
                'subtotals' => [
                    SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL => $this->getSubtotal(
                        SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL,
                        SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL,
                        10.00,
                        '$',
                        true
                    )->setOperation(Subtotal::OPERATION_ADD),
                    SubtotalProvider::ORDER_DISCOUNT_SUBTOTAL => $this->getSubtotal(
                        SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL,
                        SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL,
                        20.00,
                        '$',
                        true
                    )->setOperation(Subtotal::OPERATION_ADD),
                ],
                'amount' => 30.00,
                'discounts' => new ArrayCollection([
                    $this->getEntity(OrderDiscount::class, ['amount' => 5.00]),
                ]),
                'result' => 25.00,
            ],
            'operation subtract and unsupported entity' => [
                'subtotals' => [
                    SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL => $this->getSubtotal(
                        SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL,
                        SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL,
                        10.00,
                        '$',
                        true
                    )->setOperation(Subtotal::OPERATION_SUBTRACTION),
                    SubtotalProvider::ORDER_DISCOUNT_SUBTOTAL => $this->getSubtotal(
                        SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL,
                        SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL,
                        10.00,
                        '$',
                        true
                    )->setOperation(Subtotal::OPERATION_SUBTRACTION),
                ],
                'amount' => 20.00,
                'discounts' => new ArrayCollection([
                    $this->getEntity(OrderDiscount::class, ['amount' => 1.00]),
                    $this->getEntity(OrderDiscount::class, ['amount' => 2.00]),
                    $this->getEntity(OrderDiscount::class, ['amount' => 2.00]),
                ]),
                'result' => 15.00,
            ],
            'operation subtract with negative result unsupported entity' => [
                'subtotals' => [
                    SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL => $this->getSubtotal(
                        SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL,
                        SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL,
                        10.00,
                        '$',
                        true
                    )->setOperation(Subtotal::OPERATION_SUBTRACTION),
                    SubtotalProvider::ORDER_DISCOUNT_SUBTOTAL => $this->getSubtotal(
                        SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL,
                        SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL,
                        20.00,
                        '$',
                        true
                    )->setOperation(Subtotal::OPERATION_SUBTRACTION),
                ],
                'amount' => 10.00,
                'discounts' => new ArrayCollection([
                    $this->getEntity(OrderDiscount::class, ['amount' => 10.00]),
                    $this->getEntity(OrderDiscount::class, ['amount' => 20.00]),
                    $this->getEntity(OrderDiscount::class, ['amount' => 30.00]),
                ]),
                'result' => 0.00,
            ],
        ];
    }
}
