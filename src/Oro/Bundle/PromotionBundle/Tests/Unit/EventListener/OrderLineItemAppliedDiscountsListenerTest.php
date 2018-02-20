<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\EventListener;

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

class OrderLineItemAppliedDiscountsListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TaxProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $taxProvider;

    /**
     * @var TaxationSettingsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $taxationSettingsProvider;

    /**
     * @var LineItemSubtotalProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $lineItemSubtotalProvider;

    /**
     * @var AppliedDiscountsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $appliedDiscountsProvider;

    /**
     * @var OrderLineItemAppliedDiscountsListener
     */
    protected $discountsListener;

    public function setUp()
    {
        $this->taxProvider = $this->createMock(TaxProviderInterface::class);
        $taxProviderRegistry = $this->createMock(TaxProviderRegistry::class);
        $taxProviderRegistry->expects($this->any())
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

    public function testOnOrderEventWithTaxation()
    {
        $order = $this->prepareOrder();
        $this->taxationSettingsProvider->expects($this->once())->method('isEnabled')->willReturn(true);

        $taxesRow = new ResultElement();
        $taxesRow->offsetSet(ResultElement::INCLUDING_TAX, 100);
        $taxesRow->offsetSet(ResultElement::EXCLUDING_TAX, 50);
        $result = new Result();
        $result->offsetSet(Result::ROW, $taxesRow);
        $this->taxProvider->expects($this->atLeastOnce())->method('getTax')->willReturn($result);

        $event = new OrderEvent($this->createMock(FormInterface::class), $order);
        $this->discountsListener->onOrderEvent($event);

        $this->assertEquals([
            [
                'appliedDiscountsAmount' => 11,
                'rowTotalAfterDiscountExcludingTax' => 50 - 11,
                'rowTotalAfterDiscountIncludingTax' => 100 - 11,
                'currency' => 'USD',
            ],
            [
                'appliedDiscountsAmount' => 22,
                'rowTotalAfterDiscountExcludingTax' => 50 - 22,
                'rowTotalAfterDiscountIncludingTax' => 100 - 22,
                'currency' => 'USD',
            ],
            [
                'appliedDiscountsAmount' => 0,
                'rowTotalAfterDiscountExcludingTax' => 50,
                'rowTotalAfterDiscountIncludingTax' => 100,
                'currency' => 'USD',
            ],
        ], $event->getData()->offsetGet('appliedDiscounts'));
    }

    public function testOnOrderEventWithoutTaxation()
    {
        $order = $this->prepareOrder();

        $this->taxationSettingsProvider->expects($this->once())->method('isEnabled')->willReturn(false);

        $this->lineItemSubtotalProvider->expects($this->atLeastOnce())->method('getRowTotal')->willReturn(42);

        $event = new OrderEvent($this->createMock(FormInterface::class), $order);
        $this->discountsListener->onOrderEvent($event);

        $this->assertEquals([
            [
                'appliedDiscountsAmount' => 11,
                'rowTotalAfterDiscount' => 42 - 11,
                'currency' => 'USD',
            ],
            [
                'appliedDiscountsAmount' => 22,
                'rowTotalAfterDiscount' => 42 - 22,
                'currency' => 'USD',
            ],
            [
                'appliedDiscountsAmount' => 0,
                'rowTotalAfterDiscount' => 42,
                'currency' => 'USD',
            ],
        ], $event->getData()->offsetGet('appliedDiscounts'));
    }

    /**
     * @return Order
     */
    protected function prepareOrder()
    {
        $order = new Order();
        $lineItem1 = (new OrderLineItem())->setCurrency('USD');
        $lineItem2 = (new OrderLineItem())->setCurrency('USD');
        $lineItem3 = (new OrderLineItem())->setCurrency('USD');
        $order->addLineItem($lineItem1);
        $order->addLineItem($lineItem2);
        $order->addLineItem($lineItem3);

        $this->appliedDiscountsProvider->expects($this->atLeastOnce())
            ->method('getDiscountsAmountByLineItem')
            ->will($this->returnValueMap([
                [$lineItem1, 11],
                [$lineItem2, 22],
                [$lineItem3, 0],
            ]));

        return $order;
    }
}
