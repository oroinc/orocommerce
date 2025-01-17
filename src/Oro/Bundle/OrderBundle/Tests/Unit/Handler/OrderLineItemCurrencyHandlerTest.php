<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Handler\OrderLineItemCurrencyHandler;
use Symfony\Component\Form\FormInterface;

class OrderLineItemCurrencyHandlerTest extends \PHPUnit\Framework\TestCase
{
    private OrderLineItemCurrencyHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->handler = new OrderLineItemCurrencyHandler();
    }

    public function testResetLineItemsPricesWithCurrencyEquals(): void
    {
        $form = $this->createMock(FormInterface::class);

        $orderLineItem = new OrderLineItem();
        $orderLineItem->setCurrency('USD');

        $order = new Order();
        $order->setCurrency('USD');
        $order->setLineItems(new ArrayCollection([$orderLineItem]));

        $this->handler->resetLineItemsPrices($form, $order);

        self::assertEquals('USD', $orderLineItem->getCurrency());
    }

    public function testResetLineItemsPricesWithCurrencyNotEquals(): void
    {
        $form = $this->createMock(FormInterface::class);

        $orderLineItem = new OrderLineItem();
        $orderLineItem->setCurrency('USD');

        $order = new Order();
        $order->setCurrency('EUR');
        $order->setLineItems(new ArrayCollection([$orderLineItem]));

        $this->handler->resetLineItemsPrices($form, $order);

        self::assertNull($orderLineItem->getCurrency());
    }

    public function testResetLineItemsPricesWithCurrencyNotEqualsAndPriceChanged(): void
    {
        $priceValueForm = $this->createMock(FormInterface::class);
        $priceValueForm->expects(self::once())
            ->method('getData')
            ->willReturn(true);

        $priceForm = $this->createMock(FormInterface::class);
        $priceForm->expects(self::once())
            ->method('offsetExists')
            ->with('is_price_changed')
            ->willReturn(true);
        $priceForm->expects(self::once())
            ->method('offsetGet')
            ->with('is_price_changed')
            ->willReturn($priceValueForm);

        $lineItemForm = $this->createMock(FormInterface::class);
        $lineItemForm->expects(self::once())
            ->method('offsetExists')
            ->with('price')
            ->willReturn(true);
        $lineItemForm->expects(self::once())
            ->method('offsetGet')
            ->with('price')
            ->willReturn($priceForm);

        $lineItemsForm = $this->createMock(FormInterface::class);
        $lineItemsForm->expects(self::once())
            ->method('offsetExists')
            ->willReturn(true);
        $lineItemsForm->expects(self::once())
            ->method('offsetGet')
            ->willReturn($lineItemForm);

        $price = new Price();
        $price->setValue(1);
        $price->setCurrency('EUR');

        $orderLineItem = new OrderLineItem();
        $orderLineItem->setCurrency('EUR');
        $orderLineItem->setValue(1.0);

        $order = new Order();
        $order->setCurrency('USD');
        $order->setLineItems(new ArrayCollection([$orderLineItem]));

        $this->handler->resetLineItemsPrices($lineItemsForm, $order);

        self::assertEquals($price, $orderLineItem->getPrice());
    }

    public function testResetLineItemsPricesWithCurrencyNotEqualsAndPriceNotChanged(): void
    {
        $priceValueForm = $this->createMock(FormInterface::class);
        $priceValueForm->expects(self::once())
            ->method('getData')
            ->willReturn(false);

        $priceForm = $this->createMock(FormInterface::class);
        $priceForm->expects(self::once())
            ->method('offsetExists')
            ->with('is_price_changed')
            ->willReturn(true);
        $priceForm->expects(self::once())
            ->method('offsetGet')
            ->with('is_price_changed')
            ->willReturn($priceValueForm);

        $lineItemForm = $this->createMock(FormInterface::class);
        $lineItemForm->expects(self::once())
            ->method('offsetExists')
            ->with('price')
            ->willReturn(true);
        $lineItemForm->expects(self::once())
            ->method('offsetGet')
            ->with('price')
            ->willReturn($priceForm);

        $lineItemsForm = $this->createMock(FormInterface::class);
        $lineItemsForm->expects(self::once())
            ->method('offsetExists')
            ->willReturn(true);
        $lineItemsForm->expects(self::once())
            ->method('offsetGet')
            ->willReturn($lineItemForm);

        $orderLineItem = new OrderLineItem();
        $orderLineItem->setCurrency('EUR');
        $orderLineItem->setValue(1.0);

        $order = new Order();
        $order->setCurrency('USD');
        $order->setLineItems(new ArrayCollection([$orderLineItem]));

        $this->handler->resetLineItemsPrices($lineItemsForm, $order);

        self::assertNull($orderLineItem->getPrice());
    }

    public function testResetLineItemsPricesWithCurrencyNotEqualsAndLineItemNotExist(): void
    {
        $lineItemsForm = $this->createMock(FormInterface::class);
        $lineItemsForm->expects(self::once())
            ->method('offsetExists')
            ->willReturn(false);

        $orderLineItem = new OrderLineItem();
        $orderLineItem->setCurrency('EUR');
        $orderLineItem->setValue(1.0);

        $order = new Order();
        $order->setCurrency('USD');
        $order->setLineItems(new ArrayCollection([$orderLineItem]));

        $this->handler->resetLineItemsPrices($lineItemsForm, $order);

        self::assertNull($orderLineItem->getPrice());
    }

    public function testResetLineItemsPricesWithCurrencyNotEqualsAndLineItemPriceNotExist(): void
    {
        $lineItemForm = $this->createMock(FormInterface::class);
        $lineItemForm->expects(self::once())
            ->method('offsetExists')
            ->with('price')
            ->willReturn(false);

        $lineItemsForm = $this->createMock(FormInterface::class);
        $lineItemsForm->expects(self::once())
            ->method('offsetExists')
            ->willReturn(true);
        $lineItemsForm->expects(self::once())
            ->method('offsetGet')
            ->willReturn($lineItemForm);

        $orderLineItem = new OrderLineItem();
        $orderLineItem->setCurrency('EUR');
        $orderLineItem->setValue(1.0);

        $order = new Order();
        $order->setCurrency('USD');
        $order->setLineItems(new ArrayCollection([$orderLineItem]));

        $this->handler->resetLineItemsPrices($lineItemsForm, $order);

        self::assertNull($orderLineItem->getPrice());
    }
}
