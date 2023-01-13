<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type\EventListener;

use Oro\Bundle\CurrencyBundle\Converter\RateConverterInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Form\Type\EventListener\SubtotalSubscriber;
use Oro\Bundle\OrderBundle\Handler\OrderLineItemCurrencyHandler;
use Oro\Bundle\OrderBundle\Pricing\PriceMatcher;
use Oro\Bundle\OrderBundle\Provider\DiscountSubtotalProvider;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class SubtotalSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var TotalProcessorProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $totalProvider;

    /** @var LineItemSubtotalProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $lineItemSubtotalProvider;

    /** @var DiscountSubtotalProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $discountSubtotalProvider;

    /** @var PriceMatcher|\PHPUnit\Framework\MockObject\MockObject */
    private $priceMatcher;

    /** @var RateConverterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $rateConverter;

    /** @var OrderLineItemCurrencyHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $currencyHandler;

    /** @var SubtotalSubscriber */
    private $subscriber;

    protected function setUp(): void
    {
        $this->totalProvider = $this->createMock(TotalProcessorProvider::class);
        $this->lineItemSubtotalProvider = $this->createMock(LineItemSubtotalProvider::class);
        $this->discountSubtotalProvider = $this->createMock(DiscountSubtotalProvider::class);
        $this->priceMatcher = $this->createMock(PriceMatcher::class);
        $this->rateConverter = $this->createMock(RateConverterInterface::class);
        $this->currencyHandler = $this->createMock(OrderLineItemCurrencyHandler::class);

        $totalHelper = new TotalHelper(
            $this->totalProvider,
            $this->lineItemSubtotalProvider,
            $this->discountSubtotalProvider,
            $this->rateConverter
        );
        $this->subscriber = new SubtotalSubscriber($totalHelper, $this->priceMatcher, $this->currencyHandler);
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertEquals(
            [
                FormEvents::SUBMIT => 'onSubmitEventListener'
            ],
            SubtotalSubscriber::getSubscribedEvents()
        );
    }

    public function testOnSubmitEventListenerOnNotOrder(): void
    {
        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getForm');
        $event->expects($this->once())
            ->method('getData');
        $event->expects($this->never())
            ->method('setData');

        $this->subscriber->onSubmitEventListener($event);
    }

    public function testOnSubmitEventListenerOnOrderEmptyTotals(): void
    {
        $order = $this->prepareOrder();
        $event = $this->prepareEvent($order);

        $this->lineItemSubtotalProvider->expects($this->any())
            ->method('getSubtotal')
            ->willReturn(new Subtotal());

        $this->totalProvider->expects($this->once())
            ->method('enableRecalculation')
            ->willReturnSelf();

        $this->totalProvider->expects($this->any())
            ->method('getTotal')
            ->with($order)
            ->willReturn(new Subtotal());

        $this->discountSubtotalProvider->expects($this->any())
            ->method('getSubtotal')
            ->willReturn([]);

        $this->subscriber->onSubmitEventListener($event);
        $this->assertEquals(0, $order->getTotal());
        $this->assertEquals(0, $order->getSubtotal());
        $this->assertEquals(0, $order->getTotalDiscounts()->getValue());
    }

    public function testOnSubmitEventListenerOnOrder(): void
    {
        $order = $this->prepareOrder();
        $event = $this->prepareEvent($order);
        $this->prepareProviders();

        $this->subscriber->onSubmitEventListener($event);
        $this->assertEquals(90, $order->getTotal());
        $this->assertEquals(42, $order->getSubtotal());
        $this->assertEquals(2, $order->getTotalDiscounts()->getValue());
        $discounts = $order->getDiscounts();
        $this->assertEquals(10.00, $discounts[0]->getPercent());
    }

    public function prepareProviders(): void
    {
        $subtotal = new Subtotal();
        $subtotalAmount = 42;
        $subtotal->setType(LineItemSubtotalProvider::TYPE);
        $subtotal->setAmount($subtotalAmount);

        $discountSubtotal = new Subtotal();
        $discountSubtotalAmount = 42;
        $discountSubtotal->setType(DiscountSubtotalProvider::TYPE);
        $discountSubtotal->setAmount($discountSubtotalAmount);

        $discountSubtotal2 = new Subtotal();
        $discountSubtotalAmount2 = -40;
        $discountSubtotal2->setType(DiscountSubtotalProvider::TYPE);
        $discountSubtotal2->setAmount($discountSubtotalAmount2);

        $total = new Subtotal();
        $totalAmount = 90;
        $total->setType(TotalProcessorProvider::TYPE);
        $total->setAmount($totalAmount);

        $this->lineItemSubtotalProvider->expects($this->any())
            ->method('getSubtotal')
            ->willReturn($subtotal);

        $this->discountSubtotalProvider->expects($this->any())
            ->method('getSubtotal')
            ->willReturn([$discountSubtotal, $discountSubtotal2]);

        $this->priceMatcher->expects($this->any())
            ->method('addMatchingPrices');

        $this->totalProvider->expects($this->any())
            ->method('enableRecalculation')
            ->willReturnSelf();

        $this->totalProvider->expects($this->any())
            ->method('getTotal')
            ->willReturn($total);
    }

    private function prepareOrder(): Order
    {
        $order = new Order();
        $discount1 = new OrderDiscount();
        $discount1->setType(OrderDiscount::TYPE_AMOUNT);
        $discount1->setAmount(4.2);
        $order->addDiscount($discount1);

        return $order;
    }

    private function prepareEvent(Order $order): FormEvent
    {
        $form = $this->createMock(FormInterface::class);

        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getForm')
            ->willReturn($form);
        $event->expects($this->once())
            ->method('setData');

        $form->expects($this->exactly(2))
            ->method('has')
            ->willReturn(true);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn($order);

        $subForm = $this->createMock(FormInterface::class);
        $subForm->expects($this->once())
            ->method('submit')
            ->willReturnSelf();

        $form->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['lineItems', $subForm],
                ['discountsSum', $subForm]
            ]);

        return $event;
    }
}
