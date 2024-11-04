<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\Order;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\EventListener\Order\OrderProductKitLineItemListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Twig\Environment;

class OrderProductKitLineItemListenerTest extends TestCase
{
    private Environment|MockObject $twig;

    private OrderProductKitLineItemListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->twig = $this->createMock(Environment::class);

        $this->listener = new OrderProductKitLineItemListener($this->twig);
    }

    public function testOnOrderEventWhenNoLineItems(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects(self::once())
            ->method('get')
            ->willReturn($form);
        $order = new Order();
        $event = new OrderEvent($form, $order);

        self::assertEquals(new \ArrayObject(), $event->getData());

        $this->listener->onOrderEvent($event);

        self::assertEquals(
            new \ArrayObject(['kitItemLineItems' => [], 'checksum' => [], 'disabledKitPrices' => []]),
            $event->getData()
        );
    }

    public function testOnOrderEventWhenHasLineItemWithoutData(): void
    {
        $form = $this->createMock(FormInterface::class);
        $lineItemForm = $this->createMock(FormInterface::class);
        $form
            ->expects(self::once())
            ->method('get')
            ->willReturn($lineItemForm);
        $order = new Order();
        $event = new OrderEvent($form, $order);

        self::assertEquals(new \ArrayObject(), $event->getData());

        $this->listener->onOrderEvent($event);

        self::assertEquals(
            new \ArrayObject(['kitItemLineItems' => [], 'checksum' => [], 'disabledKitPrices' => []]),
            $event->getData()
        );
    }

    public function testOnOrderEventWhenHasLineItemWithoutProduct(): void
    {
        $form = $this->createMock(FormInterface::class);
        $lineItemForm = $this->createMock(FormInterface::class);
        $lineItem = new OrderLineItem();
        $lineItemForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn($lineItem);
        $form->expects(self::once())
            ->method('get')
            ->with('lineItems')
            ->willReturn($lineItemForm);
        $lineItemForm->expects(self::once())
            ->method('all')
            ->willReturn([$lineItemForm]);
        $order = new Order();
        $event = new OrderEvent($form, $order);

        self::assertEquals(new \ArrayObject(), $event->getData());

        $this->listener->onOrderEvent($event);

        self::assertEquals(
            new \ArrayObject(['kitItemLineItems' => [], 'checksum' => [], 'disabledKitPrices' => []]),
            $event->getData()
        );
    }

    public function testOnOrderEventWhenHasLineItemWithNotProductKit(): void
    {
        $form = $this->createMock(FormInterface::class);
        $lineItemForm = $this->createMock(FormInterface::class);
        $lineItem = (new OrderLineItem())
            ->setProduct(new Product());
        $lineItemForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn($lineItem);
        $form->expects(self::once())
            ->method('get')
            ->with('lineItems')
            ->willReturn($lineItemForm);
        $lineItemForm->expects(self::once())
            ->method('all')
            ->willReturn([$lineItemForm]);
        $order = new Order();
        $event = new OrderEvent($form, $order);

        self::assertEquals(new \ArrayObject(), $event->getData());

        $this->listener->onOrderEvent($event);

        self::assertEquals(
            new \ArrayObject(['kitItemLineItems' => [], 'checksum' => [], 'disabledKitPrices' => []]),
            $event->getData()
        );
    }

    public function testOnOrderEventWhenHasLineItemWithProductKit(): void
    {
        $form = $this->createMock(FormInterface::class);
        $lineItemForm = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('get')
            ->with('lineItems')
            ->willReturn($lineItemForm);
        $lineItemForm->expects(self::once())
            ->method('all')
            ->willReturn([$lineItemForm]);
        $order = new Order();
        $event = new OrderEvent($form, $order);
        $lineItem = (new OrderLineItem())
            ->setProduct((new Product())->setType(Product::TYPE_KIT))
            ->setChecksum('sample_checksum');
        $lineItemForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn($lineItem);
        $formView = new FormView();
        $formView->vars['full_name'] = 'form_full_name';
        $formView->children['kitItemLineItems'] = new FormView();
        $lineItemForm
            ->expects(self::once())
            ->method('createView')
            ->willReturn($formView);

        $html = 'rendered template';
        $this->twig
            ->expects(self::once())
            ->method('render')
            ->with('@OroOrder/Form/kitItemLineItems.html.twig', ['form' => $formView['kitItemLineItems']])
            ->willReturn($html);

        self::assertEquals(new \ArrayObject(), $event->getData());

        $this->listener->onOrderEvent($event);

        self::assertEquals(
            new \ArrayObject(
                [
                    'kitItemLineItems' => [$formView->vars['full_name'] => $html],
                    'checksum' => [$formView->vars['full_name'] => $lineItem->getChecksum()],
                    'disabledKitPrices' => [$formView->vars['full_name'] => true],
                ]
            ),
            $event->getData()
        );
    }
}
