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
    /** @var OrderLineItemCurrencyHandler */
    private $handler;

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

        $this->assertEquals('USD', $orderLineItem->getCurrency());
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

        $this->assertNull($orderLineItem->getCurrency());
    }

    public function testResetLineItemsPricesWithCurrencyNotEqualsAndPriceChanged(): void
    {
        $priceValueForm = $this->createMock(FormInterface::class);
        $priceValueForm
            ->expects($this->once())
            ->method('getData')
            ->willReturn(true);

        $priceForm = $lineItemsForm = $this->createMock(FormInterface::class);
        $priceForm
            ->expects($this->once())
            ->method('offsetExists')
            ->with('is_price_changed')
            ->willReturn(true);
        $priceForm
            ->expects($this->once())
            ->method('offsetGet')
            ->with('is_price_changed')
            ->willReturn($priceValueForm);

        $lineItemForm = $this->createMock(FormInterface::class);
        $lineItemForm
            ->expects($this->once())
            ->method('offsetExists')
            ->with('price')
            ->willReturn(true);
        $lineItemForm
            ->expects($this->once())
            ->method('offsetGet')
            ->with('price')
            ->willReturn($priceForm);

        $lineItemsForm = $this->createMock(FormInterface::class);
        $lineItemsForm
            ->expects($this->once())
            ->method('offsetExists')
            ->willReturn(true);
        $lineItemsForm
            ->expects($this->once())
            ->method('offsetGet')
            ->willReturn($lineItemForm);

        $price = new Price();
        $price->setCurrency('EUR')->setValue(1);

        $orderLineItem = new OrderLineItem();
        $orderLineItem->setCurrency('EUR');
        $orderLineItem->setPrice($price);

        $order = new Order();
        $order->setCurrency('USD');
        $order->setLineItems(new ArrayCollection([$orderLineItem]));

        $this->handler->resetLineItemsPrices($lineItemsForm, $order);

        $this->assertEquals($price, $orderLineItem->getPrice());
    }

    public function testResetLineItemsPricesWithCurrencyNotEqualsAndPriceNotChanged(): void
    {
        $priceValueForm = $this->createMock(FormInterface::class);
        $priceValueForm
            ->expects($this->once())
            ->method('getData')
            ->willReturn(false);

        $priceForm = $lineItemsForm = $this->createMock(FormInterface::class);
        $priceForm
            ->expects($this->once())
            ->method('offsetExists')
            ->with('is_price_changed')
            ->willReturn(true);
        $priceForm
            ->expects($this->once())
            ->method('offsetGet')
            ->with('is_price_changed')
            ->willReturn($priceValueForm);

        $lineItemForm = $this->createMock(FormInterface::class);
        $lineItemForm
            ->expects($this->once())
            ->method('offsetExists')
            ->with('price')
            ->willReturn(true);
        $lineItemForm
            ->expects($this->once())
            ->method('offsetGet')
            ->with('price')
            ->willReturn($priceForm);

        $lineItemsForm = $this->createMock(FormInterface::class);
        $lineItemsForm
            ->expects($this->once())
            ->method('offsetExists')
            ->willReturn(true);
        $lineItemsForm
            ->expects($this->once())
            ->method('offsetGet')
            ->willReturn($lineItemForm);

        $price = new Price();
        $price->setCurrency('EUR')->setValue(1);

        $orderLineItem = new OrderLineItem();
        $orderLineItem->setCurrency('EUR');
        $orderLineItem->setPrice($price);

        $order = new Order();
        $order->setCurrency('USD');
        $order->setLineItems(new ArrayCollection([$orderLineItem]));

        $this->handler->resetLineItemsPrices($lineItemsForm, $order);

        $this->assertNull($orderLineItem->getPrice());
    }

    public function testResetLineItemsPricesWithCurrencyNotEqualsAndLineItemNotExist(): void
    {
        $lineItemsForm = $this->createMock(FormInterface::class);
        $lineItemsForm
            ->expects($this->once())
            ->method('offsetExists')
            ->willReturn(false);

        $price = new Price();
        $price->setCurrency('EUR')->setValue(1);

        $orderLineItem = new OrderLineItem();
        $orderLineItem->setCurrency('EUR');
        $orderLineItem->setPrice($price);

        $order = new Order();
        $order->setCurrency('USD');
        $order->setLineItems(new ArrayCollection([$orderLineItem]));

        $this->handler->resetLineItemsPrices($lineItemsForm, $order);

        $this->assertNull($orderLineItem->getPrice());
    }

    public function testResetLineItemsPricesWithCurrencyNotEqualsAndLineItemPriceNotExist(): void
    {
        $lineItemForm = $this->createMock(FormInterface::class);
        $lineItemForm
            ->expects($this->once())
            ->method('offsetExists')
            ->with('price')
            ->willReturn(false);

        $lineItemsForm = $this->createMock(FormInterface::class);
        $lineItemsForm
            ->expects($this->once())
            ->method('offsetExists')
            ->willReturn(true);
        $lineItemsForm
            ->expects($this->once())
            ->method('offsetGet')
            ->willReturn($lineItemForm);

        $price = new Price();
        $price->setCurrency('EUR')->setValue(1);

        $orderLineItem = new OrderLineItem();
        $orderLineItem->setCurrency('EUR');
        $orderLineItem->setPrice($price);

        $order = new Order();
        $order->setCurrency('USD');
        $order->setLineItems(new ArrayCollection([$orderLineItem]));

        $this->handler->resetLineItemsPrices($lineItemsForm, $order);

        $this->assertNull($orderLineItem->getPrice());
    }
}
