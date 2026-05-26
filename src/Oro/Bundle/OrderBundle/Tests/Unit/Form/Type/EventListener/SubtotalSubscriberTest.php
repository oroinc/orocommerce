<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type\EventListener;

use Oro\Bundle\CurrencyBundle\Converter\RateConverterInterface;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Form\Type\EventListener\SubtotalSubscriber;
use Oro\Bundle\OrderBundle\Handler\OrderLineItemCurrencyHandler;
use Oro\Bundle\OrderBundle\Pricing\PriceMatcher;
use Oro\Bundle\OrderBundle\Provider\DiscountSubtotalProvider;
use Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM\Stub\OrderStub as Order;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class SubtotalSubscriberTest extends TestCase
{
    private TotalProcessorProvider&MockObject $totalProvider;

    private LineItemSubtotalProvider&MockObject $lineItemSubtotalProvider;

    private DiscountSubtotalProvider&MockObject $discountSubtotalProvider;

    private PriceMatcher&MockObject $priceMatcher;

    private SubtotalSubscriber $subscriber;

    #[\Override]
    protected function setUp(): void
    {
        $this->totalProvider = $this->createMock(TotalProcessorProvider::class);
        $this->lineItemSubtotalProvider = $this->createMock(LineItemSubtotalProvider::class);
        $this->discountSubtotalProvider = $this->createMock(DiscountSubtotalProvider::class);
        $this->priceMatcher = $this->createMock(PriceMatcher::class);
        $rateConverter = $this->createMock(RateConverterInterface::class);
        $currencyHandler = $this->createMock(OrderLineItemCurrencyHandler::class);

        $totalHelper = new TotalHelper(
            $this->totalProvider,
            $this->lineItemSubtotalProvider,
            $this->discountSubtotalProvider,
            $rateConverter
        );
        $this->subscriber = new SubtotalSubscriber($totalHelper, $this->priceMatcher, $currencyHandler);
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertEquals(
            [
                FormEvents::PRE_SET_DATA => 'onPreSetDataEventListener',
                FormEvents::SUBMIT => 'onSubmitEventListener',
            ],
            SubtotalSubscriber::getSubscribedEvents()
        );
    }

    public function testOnPreSetDataEventListenerOnNotOrder(): void
    {
        $event = $this->createMock(FormEvent::class);
        $event->expects(self::once())
            ->method('getForm');
        $event->expects(self::once())
            ->method('getData');

        $this->subscriber->onPreSetDataEventListener($event);
    }

    public function testOnPreSetDataEventListenerOnOrderEmptyTotals(): void
    {
        $order = $this->prepareOrder();

        $subForm = $this->createMock(FormInterface::class);
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('has')
            ->with('lineItems')
            ->willReturn(true);
        $form->expects(self::once())
            ->method('get')
            ->with('lineItems')
            ->willReturn($subForm);

        $event = $this->createMock(FormEvent::class);
        $event->expects(self::once())
            ->method('getForm')
            ->willReturn($form);
        $event->expects(self::once())
            ->method('getData')
            ->willReturn($order);

        $this->lineItemSubtotalProvider->expects(self::once())
            ->method('getSubtotal')
            ->willReturn(new Subtotal());

        $this->totalProvider->expects(self::once())
            ->method('enableRecalculation')
            ->willReturnSelf();

        $this->totalProvider->expects(self::once())
            ->method('getTotal')
            ->with($order)
            ->willReturn(new Subtotal());

        $this->discountSubtotalProvider->expects(self::once())
            ->method('getSubtotal')
            ->willReturn([]);

        $this->subscriber->onPreSetDataEventListener($event);
        self::assertEquals(0, $order->getTotal());
        self::assertEquals(0, $order->getSubtotal());
        self::assertEquals(0, $order->getTotalDiscounts()->getValue());
    }

    public function testOnPreSetDataEventListenerOnOrder(): void
    {
        $order = $this->prepareOrder();

        $subForm = $this->createMock(FormInterface::class);
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('has')
            ->with('lineItems')
            ->willReturn(true);
        $form->expects(self::once())
            ->method('get')
            ->with('lineItems')
            ->willReturn($subForm);

        $event = $this->createMock(FormEvent::class);
        $event->expects(self::once())
            ->method('getForm')
            ->willReturn($form);
        $event->expects(self::once())
            ->method('getData')
            ->willReturn($order);

        $this->prepareProviders();

        $this->subscriber->onPreSetDataEventListener($event);
        self::assertEquals(90, $order->getTotal());
        self::assertEquals(42, $order->getSubtotal());
        self::assertEquals(2, $order->getTotalDiscounts()->getValue());
        $discounts = $order->getDiscounts();
        self::assertEquals(10.00, $discounts[0]->getPercent());
    }

    public function testOnPreSetDataEventListenerOnIncorrectOrderTotal(): void
    {
        $order = $this->prepareOrder();
        $order->getTotalObject()->setValue(89.00);
        $order->setSerializedData(['precalculatedTotal' => 90.00]);

        $subForm = $this->createMock(FormInterface::class);
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('has')
            ->with('lineItems')
            ->willReturn(true);
        $form->expects(self::once())
            ->method('get')
            ->with('lineItems')
            ->willReturn($subForm);

        $event = $this->createMock(FormEvent::class);
        $event->expects(self::once())
            ->method('getForm')
            ->willReturn($form);
        $event->expects(self::once())
            ->method('getData')
            ->willReturn($order);

        $this->prepareProviders();

        $this->subscriber->onPreSetDataEventListener($event);
        self::assertEquals(89, $order->getTotal());
        self::assertEquals(42, $order->getSubtotal());
        self::assertEquals(2, $order->getTotalDiscounts()->getValue());
        $discounts = $order->getDiscounts();
        self::assertEquals(10.00, $discounts[0]->getPercent());
    }

    public function testOnSubmitEventListenerOnNotOrder(): void
    {
        $event = $this->createMock(FormEvent::class);
        $event->expects(self::once())
            ->method('getForm');
        $event->expects(self::once())
            ->method('getData');
        $event->expects(self::never())
            ->method('setData');

        $this->subscriber->onSubmitEventListener($event);
    }

    public function testOnSubmitEventListenerOnOrderEmptyTotals(): void
    {
        $order = $this->prepareOrder();
        $event = $this->prepareEvent($order);

        $this->lineItemSubtotalProvider->expects(self::any())
            ->method('getSubtotal')
            ->willReturn(new Subtotal());

        $this->totalProvider->expects(self::once())
            ->method('enableRecalculation')
            ->willReturnSelf();

        $this->totalProvider->expects(self::any())
            ->method('getTotal')
            ->with($order)
            ->willReturn(new Subtotal());

        $this->discountSubtotalProvider->expects(self::any())
            ->method('getSubtotal')
            ->willReturn([]);

        $this->subscriber->onSubmitEventListener($event);
        self::assertEquals(0, $order->getTotal());
        self::assertEquals(0, $order->getSubtotal());
        self::assertEquals(0, $order->getTotalDiscounts()->getValue());
    }

    public function testOnSubmitEventListenerOnOrder(): void
    {
        $order = $this->prepareOrder();
        $event = $this->prepareEvent($order);
        $this->prepareProviders();

        $this->subscriber->onSubmitEventListener($event);
        self::assertEquals(90, $order->getTotal());
        self::assertEquals(42, $order->getSubtotal());
        self::assertEquals(2, $order->getTotalDiscounts()->getValue());
        $discounts = $order->getDiscounts();
        self::assertEquals(10.00, $discounts[0]->getPercent());
    }

    public function testOnSubmitEventListenerOnIncorrectOrderTotal(): void
    {
        $order = $this->prepareOrder();
        $order->getTotalObject()->setValue(89.00);
        $order->setSerializedData(['precalculatedTotal' => 90.00]);
        $event = $this->prepareEvent($order);
        $this->prepareProviders();

        $this->subscriber->onSubmitEventListener($event);
        self::assertEquals(89, $order->getTotal());
        self::assertEquals(42, $order->getSubtotal());
        self::assertEquals(2, $order->getTotalDiscounts()->getValue());
        $discounts = $order->getDiscounts();
        self::assertEquals(10.00, $discounts[0]->getPercent());
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

        $this->lineItemSubtotalProvider->expects(self::any())
            ->method('getSubtotal')
            ->willReturn($subtotal);

        $this->discountSubtotalProvider->expects(self::any())
            ->method('getSubtotal')
            ->willReturn([$discountSubtotal, $discountSubtotal2]);

        $this->priceMatcher->expects(self::any())
            ->method('addMatchingPrices');

        $this->totalProvider->expects(self::any())
            ->method('enableRecalculation')
            ->willReturnSelf();

        $this->totalProvider->expects(self::any())
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
        $event->expects(self::once())
            ->method('getForm')
            ->willReturn($form);
        $event->expects(self::once())
            ->method('setData');

        $form->expects(self::exactly(2))
            ->method('has')
            ->willReturn(true);
        $event->expects(self::once())
            ->method('getData')
            ->willReturn($order);

        $subForm = $this->createMock(FormInterface::class);
        $subForm->expects(self::once())
            ->method('submit')
            ->willReturnSelf();

        $form->expects(self::any())
            ->method('get')
            ->willReturnMap([
                ['lineItems', $subForm],
                ['discountsSum', $subForm]
            ]);

        return $event;
    }
}
