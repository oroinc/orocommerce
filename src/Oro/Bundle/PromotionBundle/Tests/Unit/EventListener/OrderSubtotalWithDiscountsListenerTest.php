<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\EventListener;

use Brick\Math\BigDecimal;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\CurrencyBundle\Converter\RateConverterInterface;
use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;
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

    private RateConverterInterface|MockObject $rateConverter;
    private SubtotalProviderInterface|MockObject $subtotalProvider;
    private OrderSubtotalWithDiscountsListener $subtotalWithDiscountsListener;

    #[\Override]
    protected function setUp(): void
    {
        $this->subtotalProvider = $this->createMock(SubtotalProviderInterface::class);
        $this->rateConverter = $this->createMock(RateConverterInterface::class);
        $this->subtotalWithDiscountsListener = new OrderSubtotalWithDiscountsListener(
            $this->subtotalProvider,
            $this->rateConverter
        );
    }

    /**
     * @dataProvider getSubtotalsForSupportedEntity
     */
    public function testPrePersistPreUpdateWithSupportedEntity(
        array $subtotals,
        float $amount,
        ArrayCollection $discounts,
        float $value,
        float $baseValue
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

        $this->rateConverter->expects(self::any())
            ->method('getBaseCurrencyAmount')
            ->willReturnCallback(function (MultiCurrency $currency) {
                $value = BigDecimal::of($currency->getValue());
                $value = $value->multipliedBy(1.1);

                return $value->toFloat();
            });

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
        self::assertEquals($order->getSubtotalDiscountObject()->getValue(), $value);
        self::assertEquals($order->getSubtotalDiscountObject()->getBaseCurrencyValue(), $baseValue);

        $this->subtotalWithDiscountsListener->preUpdate($order, $preUpdateEvent);
        self::assertEquals($order->getSubtotalDiscountObject()->getValue(), $value);
        self::assertEquals($order->getSubtotalDiscountObject()->getBaseCurrencyValue(), $baseValue);
    }

    /**
     * @dataProvider getSubtotalsForUnsupportedEntity
     */
    public function testPrePersistPreUpdateWithUnsupportedEntity(
        array $subtotals,
        float $amount,
        ArrayCollection $discounts,
        float $value,
        float $baseValue
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

        $this->rateConverter->expects(self::any())
            ->method('getBaseCurrencyAmount')
            ->willReturnCallback(function (MultiCurrency $currency) {
                $value = BigDecimal::of($currency->getValue());
                $value = $value->multipliedBy(1.1);

                return $value->toFloat();
            });

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
        self::assertEquals($order->getSubtotalDiscountObject()->getValue(), $value);
        self::assertEquals($order->getSubtotalDiscountObject()->getBaseCurrencyValue(), $baseValue);

        $this->subtotalWithDiscountsListener->preUpdate($order, $preUpdateEvent);
        self::assertEquals($order->getSubtotalDiscountObject()->getValue(), $value);
        self::assertEquals($order->getSubtotalDiscountObject()->getBaseCurrencyValue(), $baseValue);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
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
                'value' => 45.0,
                'base_value' => 49.5,
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
                'value' => 5.0,
                'base_value' => 5.5,
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
                'value' => 0.0,
                'base_value' => 0.0,
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
                'value' => 0.0,
                'base_value' => 0.0,
            ]
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
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
                'value' => 25.0,
                'base_value' => 27.5,
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
                'value' => 15.0,
                'base_value' => 16.5,
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
                'value' => 0.0,
                'base_value' => 0.0,
            ],
        ];
    }
}
