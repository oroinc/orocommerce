<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\EventListener;

use Brick\Math\BigDecimal;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PromotionBundle\EventListener\OrderLineItemAppliedDiscountsListener;
use Oro\Bundle\PromotionBundle\Provider\AppliedDiscountsProvider;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TaxBundle\Provider\TaxProviderInterface;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;
use Symfony\Component\Form\FormInterface;

class OrderLineItemAppliedDiscountsListenerTest extends \PHPUnit\Framework\TestCase
{
    private const DISCOUNT1 = 111.11111111111;
    private const DISCOUNT2 = 222.22222222222;
    private const ROW_TOTAL1 = '999.9999999999999999';
    private const ROW_TOTAL2 = '888.8888888888888888';

    /** @var TaxProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $taxProvider;

    /** @var TaxationSettingsProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $taxationSettingsProvider;

    /** @var LineItemSubtotalProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $lineItemSubtotalProvider;

    /** @var AppliedDiscountsProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $appliedDiscountsProvider;

    /** @var OrderLineItemAppliedDiscountsListener */
    protected $discountsListener;

    protected function setUp(): void
    {
        $this->taxProvider = $this->createMock(TaxProviderInterface::class);
        $taxProviderRegistry = $this->createMock(TaxProviderRegistry::class);
        $taxProviderRegistry->expects(self::any())
            ->method('getEnabledProvider')
            ->willReturn($this->taxProvider);

        $this->taxationSettingsProvider = $this->createMock(TaxationSettingsProvider::class);
        $this->lineItemSubtotalProvider = $this->createMock(LineItemSubtotalProvider::class);
        $this->appliedDiscountsProvider = $this->createMock(AppliedDiscountsProvider::class);

        $this->discountsListener = new OrderLineItemAppliedDiscountsListener(
            $taxProviderRegistry,
            $this->taxationSettingsProvider,
            $this->lineItemSubtotalProvider,
            $this->appliedDiscountsProvider
        );
    }

    /**
     * @dataProvider getOnOrderEventWithTaxationDataProvider
     */
    public function testOnOrderEventWithTaxation(bool $isDiscountsIncluded, array $expectedDiscounts): void
    {
        $order = $this->prepareOrder();
        $this->taxationSettingsProvider->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);

        $taxesRow = new ResultElement();
        $taxesRow->offsetSet(ResultElement::EXCLUDING_TAX, self::ROW_TOTAL1);
        $taxesRow->offsetSet(ResultElement::INCLUDING_TAX, self::ROW_TOTAL2);
        $taxesRow->setDiscountsIncluded($isDiscountsIncluded);

        $result = new Result();
        $result->offsetSet(Result::ROW, $taxesRow);
        $this->taxProvider->expects(self::atLeastOnce())
            ->method('getTax')
            ->willReturn($result);

        $event = new OrderEvent($this->createMock(FormInterface::class), $order);
        $this->discountsListener->onOrderEvent($event);

        self::assertSame($expectedDiscounts, $event->getData()->offsetGet('appliedDiscounts'));
    }

    public function getOnOrderEventWithTaxationDataProvider(): array
    {
        $discounts = [
            [
                'appliedDiscountsAmount' => self::DISCOUNT1,
                'rowTotalAfterDiscountExcludingTax' => (string)BigDecimal::of(self::ROW_TOTAL1)->minus(self::DISCOUNT1),
                'rowTotalAfterDiscountIncludingTax' => (string)BigDecimal::of(self::ROW_TOTAL2)->minus(self::DISCOUNT1),
                'currency' => 'USD',
            ],
            [
                'appliedDiscountsAmount' => self::DISCOUNT2,
                'rowTotalAfterDiscountExcludingTax' => (string)BigDecimal::of(self::ROW_TOTAL1)->minus(self::DISCOUNT2),
                'rowTotalAfterDiscountIncludingTax' => (string)BigDecimal::of(self::ROW_TOTAL2)->minus(self::DISCOUNT2),
                'currency' => 'USD',
            ],
            [
                'appliedDiscountsAmount' => 0.0,
                'rowTotalAfterDiscountExcludingTax' => self::ROW_TOTAL1,
                'rowTotalAfterDiscountIncludingTax' => self::ROW_TOTAL2,
                'currency' => 'USD',
            ],
        ];

        $discountsAlreadyIncluded = [
            [
                'appliedDiscountsAmount' => self::DISCOUNT1,
                'rowTotalAfterDiscountExcludingTax' => self::ROW_TOTAL1,
                'rowTotalAfterDiscountIncludingTax' => self::ROW_TOTAL2,
                'currency' => 'USD',
            ],
            [
                'appliedDiscountsAmount' => self::DISCOUNT2,
                'rowTotalAfterDiscountExcludingTax' => self::ROW_TOTAL1,
                'rowTotalAfterDiscountIncludingTax' => self::ROW_TOTAL2,
                'currency' => 'USD',
            ],
            [
                'appliedDiscountsAmount' => 0.0,
                'rowTotalAfterDiscountExcludingTax' => self::ROW_TOTAL1,
                'rowTotalAfterDiscountIncludingTax' => self::ROW_TOTAL2,
                'currency' => 'USD',
            ],
        ];

        return [
            'discounts not included' => [
                'isDiscountsIncluded' => false,
                'expectedDiscounts' => $discounts,
            ],
            'discounts included' => [
                'isDiscountsIncluded' => true,
                'expectedDiscounts' => $discountsAlreadyIncluded,
            ],
        ];
    }

    public function testOnOrderEventWithoutTaxation(): void
    {
        $order = $this->prepareOrder();

        $this->taxationSettingsProvider->expects(self::once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->lineItemSubtotalProvider->expects(self::atLeastOnce())
            ->method('getRowTotal')
            ->willReturn(420);

        $event = new OrderEvent($this->createMock(FormInterface::class), $order);
        $this->discountsListener->onOrderEvent($event);

        self::assertEquals([
            [
                'appliedDiscountsAmount' => self::DISCOUNT1,
                'rowTotalAfterDiscount' => 420 - self::DISCOUNT1,
                'currency' => 'USD',
            ],
            [
                'appliedDiscountsAmount' => self::DISCOUNT2,
                'rowTotalAfterDiscount' => 420 - self::DISCOUNT2,
                'currency' => 'USD',
            ],
            [
                'appliedDiscountsAmount' => 0,
                'rowTotalAfterDiscount' => 420,
                'currency' => 'USD',
            ],
        ], $event->getData()->offsetGet('appliedDiscounts'));
    }

    /**
     * @dataProvider getOnOrderEventEmptyTaxesRowDataProvider
     */
    public function testOnOrderEventEmptyTaxesRow(bool $isDiscountsIncluded): void
    {
        $order = new Order();
        $lineItem1 = (new OrderLineItem())->setCurrency('USD');
        $order->addLineItem($lineItem1);

        $this->appliedDiscountsProvider->expects(self::atLeastOnce())
            ->method('getDiscountsAmountByLineItem')
            ->willReturnMap([
                [$lineItem1, 0],
            ]);

        $this->taxationSettingsProvider->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);

        $taxesRow = new ResultElement();
        $taxesRow->setDiscountsIncluded($isDiscountsIncluded);

        $result = new Result();
        $result->offsetSet(Result::ROW, $taxesRow);
        $this->taxProvider->expects(self::atLeastOnce())
            ->method('getTax')
            ->willReturn($result);

        $event = new OrderEvent($this->createMock(FormInterface::class), $order);
        $this->discountsListener->onOrderEvent($event);

        self::assertSame(
            [
                [
                    'appliedDiscountsAmount' => 0.0,
                    'rowTotalAfterDiscountExcludingTax' => '0.0',
                    'rowTotalAfterDiscountIncludingTax' => '0.0',
                    'currency' => 'USD'
                ]
            ],
            $event->getData()->offsetGet('appliedDiscounts')
        );
    }

    public function getOnOrderEventEmptyTaxesRowDataProvider(): array
    {
        return [
            'discounts not included' => [
                'isDiscountsIncluded' => false,
            ],
            'discounts included' => [
                'isDiscountsIncluded' => true,
            ],
        ];
    }

    protected function prepareOrder(): Order
    {
        $order = new Order();
        $lineItem1 = (new OrderLineItem())->setCurrency('USD');
        $lineItem2 = (new OrderLineItem())->setCurrency('USD');
        $lineItem3 = (new OrderLineItem())->setCurrency('USD');
        $order->addLineItem($lineItem1);
        $order->addLineItem($lineItem2);
        $order->addLineItem($lineItem3);

        $this->appliedDiscountsProvider->expects(self::atLeastOnce())
            ->method('getDiscountsAmountByLineItem')
            ->willReturnMap(
                [
                    [$lineItem1, self::DISCOUNT1],
                    [$lineItem2, self::DISCOUNT2],
                    [$lineItem3, 0],
                ]
            );

        return $order;
    }
}
