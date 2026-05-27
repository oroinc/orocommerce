<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type\EventListener;

use Oro\Bundle\OrderBundle\Form\Type\EventListener\OrderLineItemDraftDrySubmitListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

final class OrderLineItemDraftDrySubmitListenerTest extends TestCase
{
    private OrderLineItemDraftDrySubmitListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->listener = new OrderLineItemDraftDrySubmitListener();
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertSame(
            [
                FormEvents::PRE_SUBMIT => 'handleDrySubmitTriggerOnPreSubmit',
            ],
            OrderLineItemDraftDrySubmitListener::getSubscribedEvents()
        );
    }

    public function testHandleDrySubmitTriggerOnPreSubmitWhenNoDrySubmitTrigger(): void
    {
        $data = [
            'product' => 123,
            'quantity' => 5,
        ];

        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, $data);

        $this->listener->handleDrySubmitTriggerOnPreSubmit($event);

        // Data should remain unchanged when no drySubmitTrigger is present
        self::assertSame($data, $event->getData());
    }

    public function testHandleDrySubmitTriggerOnPreSubmitWhenDrySubmitTriggerIsNull(): void
    {
        $data = [
            'drySubmitTrigger' => null,
            'product' => 123,
            'quantity' => 5,
        ];

        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, $data);

        $this->listener->handleDrySubmitTriggerOnPreSubmit($event);

        // Data should remain unchanged when drySubmitTrigger is null
        self::assertSame($data, $event->getData());
    }

    public function testHandleDrySubmitTriggerOnPreSubmitWithIsFreeFormTrigger(): void
    {
        $data = [
            'drySubmitTrigger' => 'isFreeForm',
            'productSku' => 'SKU123',
            'freeFormProduct' => 'Free Form Product',
            'product' => 123,
            'productUnit' => 'item',
            'quantity' => 5,
            'price' => ['value' => 10.50, 'currency' => 'USD', 'is_price_changed' => true],
            'kitItemLineItems' => [
                ['product' => 456, 'quantity' => 2],
            ],
            'comment' => 'Test comment',
        ];

        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, $data);

        $this->listener->handleDrySubmitTriggerOnPreSubmit($event);

        $resultData = $event->getData();

        // Fields should be unset
        self::assertArrayNotHasKey('productSku', $resultData);
        self::assertArrayNotHasKey('freeFormProduct', $resultData);
        self::assertArrayNotHasKey('productUnit', $resultData);
        self::assertArrayNotHasKey('quantity', $resultData);
        self::assertArrayNotHasKey('kitItemLineItems', $resultData);

        // price array should still exist but value and is_price_changed should be unset
        self::assertArrayHasKey('price', $resultData);
        self::assertArrayNotHasKey('value', $resultData['price']);
        self::assertArrayNotHasKey('is_price_changed', $resultData['price']);
        self::assertArrayHasKey('currency', $resultData['price']);

        // Other fields should remain
        self::assertSame(123, $resultData['product']);
        self::assertSame('Test comment', $resultData['comment']);
        self::assertSame('isFreeForm', $resultData['drySubmitTrigger']);
    }

    public function testHandleDrySubmitTriggerOnPreSubmitWithProductTrigger(): void
    {
        $data = [
            'drySubmitTrigger' => 'product',
            'productSku' => 'SKU123',
            'freeFormProduct' => 'Free Form Product',
            'product' => 123,
            'productUnit' => 'item',
            'quantity' => 5,
            'price' => ['value' => 10.50, 'currency' => 'USD', 'is_price_changed' => true],
            'kitItemLineItems' => [
                ['product' => 456, 'quantity' => 2],
            ],
            'comment' => 'Test comment',
        ];

        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, $data);

        $this->listener->handleDrySubmitTriggerOnPreSubmit($event);

        $resultData = $event->getData();

        // Fields should be unset
        self::assertArrayNotHasKey('productSku', $resultData);
        self::assertArrayNotHasKey('freeFormProduct', $resultData);
        self::assertArrayNotHasKey('productUnit', $resultData);
        self::assertArrayNotHasKey('quantity', $resultData);
        self::assertArrayNotHasKey('kitItemLineItems', $resultData);

        // price array should still exist but value and is_price_changed should be unset
        self::assertArrayHasKey('price', $resultData);
        self::assertArrayNotHasKey('value', $resultData['price']);
        self::assertArrayNotHasKey('is_price_changed', $resultData['price']);
        self::assertArrayHasKey('currency', $resultData['price']);

        // Other fields should remain
        self::assertSame(123, $resultData['product']);
        self::assertSame('Test comment', $resultData['comment']);
        self::assertSame('product', $resultData['drySubmitTrigger']);
    }

    public function testHandleDrySubmitTriggerOnPreSubmitWhenIsFreeForm(): void
    {
        $data = [
            'drySubmitTrigger' => 'isFreeForm',
            'isFreeForm' => true,
            'productSku' => 'SKU123',
            'freeFormProduct' => 'Free Form Product',
            'product' => 123,
            'productUnit' => 'item',
            'quantity' => 5,
            'price' => ['value' => 10.50, 'currency' => 'USD'],
            'kitItemLineItems' => [
                ['product' => 456, 'quantity' => 2],
            ],
            'comment' => 'Test comment',
        ];

        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, $data);

        $this->listener->handleDrySubmitTriggerOnPreSubmit($event);

        $resultData = $event->getData();

        // Fields should be unset
        self::assertArrayNotHasKey('kitItemLineItems', $resultData);

        // price array should still exist but value should be unset
        self::assertArrayHasKey('price', $resultData);
        self::assertEquals(['value' => 10.50, 'currency' => 'USD'], $resultData['price']);

        // Other fields should remain
        self::assertSame('SKU123', $resultData['productSku']);
        self::assertSame('Free Form Product', $resultData['freeFormProduct']);
        self::assertSame(123, $resultData['product']);
        self::assertSame('item', $resultData['productUnit']);
        self::assertSame(5, $resultData['quantity']);
        self::assertSame('Test comment', $resultData['comment']);
        self::assertSame('isFreeForm', $resultData['drySubmitTrigger']);
    }

    public function testHandleDrySubmitTriggerOnPreSubmitWithUnhandledTrigger(): void
    {
        $data = [
            'drySubmitTrigger' => 'quantity',
            'productSku' => 'SKU123',
            'product' => 123,
            'quantity' => 5,
        ];

        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, $data);

        $this->listener->handleDrySubmitTriggerOnPreSubmit($event);

        $resultData = $event->getData();

        // Data should remain unchanged for unhandled triggers
        self::assertArrayHasKey('productSku', $resultData);
        self::assertArrayHasKey('product', $resultData);
        self::assertArrayHasKey('quantity', $resultData);
    }

    public function testHandleDrySubmitTriggerOnPreSubmitWithKitItemLineItemProductTrigger(): void
    {
        $data = [
            'drySubmitTrigger' => 'kitItemLineItems[0][product]',
            'kitItemLineItems' => [
                0 => [
                    'product' => 123,
                    'quantity' => 5,
                    'price' => ['value' => 10.50, 'currency' => 'USD'],
                ],
                1 => [
                    'product' => 456,
                    'quantity' => 3,
                    'price' => ['value' => 20.00, 'currency' => 'USD'],
                ],
            ],
        ];

        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, $data);

        $this->listener->handleDrySubmitTriggerOnPreSubmit($event);

        $resultData = $event->getData();

        // First kit item line item should have quantity and price unset
        self::assertArrayHasKey('product', $resultData['kitItemLineItems'][0]);
        self::assertArrayNotHasKey('quantity', $resultData['kitItemLineItems'][0]);
        self::assertArrayNotHasKey('price', $resultData['kitItemLineItems'][0]);

        // Second kit item line item should remain unchanged
        self::assertArrayHasKey('product', $resultData['kitItemLineItems'][1]);
        self::assertArrayHasKey('quantity', $resultData['kitItemLineItems'][1]);
        self::assertArrayHasKey('price', $resultData['kitItemLineItems'][1]);
    }

    public function testHandleDrySubmitTriggerOnPreSubmitWithKitItemLineItemQuantityTrigger(): void
    {
        $data = [
            'drySubmitTrigger' => 'kitItemLineItems[2][quantity]',
            'price' => ['value' => 10.00, 'currency' => 'USD'],
            'kitItemLineItems' => [
                2 => [
                    'product' => 789,
                    'quantity' => 10,
                    'price' => ['value' => 5.00, 'currency' => 'USD'],
                ],
            ],
        ];

        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, $data);

        $this->listener->handleDrySubmitTriggerOnPreSubmit($event);

        $resultData = $event->getData();

        // Price value should be unset but currency should remain when is_price_changed is not set.
        self::assertArrayHasKey('price', $resultData);
        self::assertArrayNotHasKey('value', $resultData['price']);
        self::assertArrayHasKey('currency', $resultData['price']);
        self::assertEquals(['currency' => 'USD'], $resultData['price']);

        // For quantity trigger, nothing should be cleared (only product trigger clears fields)
        self::assertArrayHasKey('product', $resultData['kitItemLineItems'][2]);
        self::assertArrayHasKey('quantity', $resultData['kitItemLineItems'][2]);
        self::assertArrayHasKey('price', $resultData['kitItemLineItems'][2]);
    }

    public function testHandleDrySubmitTriggerOnPreSubmitWithKitItemLineItemPriceTrigger(): void
    {
        $data = [
            'drySubmitTrigger' => 'kitItemLineItems[0][price]',
            'price' => ['value' => 20.00, 'currency' => 'USD'],
            'kitItemLineItems' => [
                0 => [
                    'product' => 111,
                    'quantity' => 7,
                    'price' => ['value' => 15.00, 'currency' => 'EUR'],
                ],
            ],
        ];

        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, $data);

        $this->listener->handleDrySubmitTriggerOnPreSubmit($event);

        $resultData = $event->getData();

        // Price value should be unset but currency should remain when is_price_changed is not set.
        self::assertArrayHasKey('price', $resultData);
        self::assertArrayNotHasKey('value', $resultData['price']);
        self::assertArrayHasKey('currency', $resultData['price']);
        self::assertEquals(['currency' => 'USD'], $resultData['price']);

        // For price trigger, nothing should be cleared (only product trigger clears fields)
        self::assertArrayHasKey('product', $resultData['kitItemLineItems'][0]);
        self::assertArrayHasKey('quantity', $resultData['kitItemLineItems'][0]);
        self::assertArrayHasKey('price', $resultData['kitItemLineItems'][0]);
    }

    public function testHandleDrySubmitTriggerOnPreSubmitWithInvalidKitItemTrigger(): void
    {
        $data = [
            'drySubmitTrigger' => 'kitItemLineItems[0]',
            'price' => ['value' => 10.00, 'currency' => 'USD'],
            'kitItemLineItems' => [
                0 => [
                    'product' => 123,
                    'quantity' => 5,
                ],
            ],
        ];

        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, $data);

        $this->listener->handleDrySubmitTriggerOnPreSubmit($event);

        $resultData = $event->getData();

        // Data should remain unchanged for invalid kit item triggers (not enough path elements)
        self::assertArrayHasKey('price', $resultData);
        self::assertEquals(['value' => 10.0, 'currency' => 'USD'], $resultData['price']);
        self::assertArrayHasKey('kitItemLineItems', $resultData);
        self::assertArrayHasKey('product', $resultData['kitItemLineItems'][0]);
        self::assertArrayHasKey('quantity', $resultData['kitItemLineItems'][0]);
    }

    public function testHandleDrySubmitTriggerOnPreSubmitWithKitItemLineItemNonProductField(): void
    {
        $data = [
            'drySubmitTrigger' => 'kitItemLineItems[0][sortOrder]',
            'price' => ['value' => 10.00, 'currency' => 'USD'],
            'kitItemLineItems' => [
                0 => [
                    'product' => 123,
                    'quantity' => 5,
                    'sortOrder' => 1,
                ],
            ],
        ];

        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, $data);

        $this->listener->handleDrySubmitTriggerOnPreSubmit($event);

        $resultData = $event->getData();

        // Data should remain unchanged for non-handled kit item fields
        self::assertArrayHasKey('price', $resultData);
        self::assertEquals(['value' => 10.0, 'currency' => 'USD'], $resultData['price']);
        self::assertArrayHasKey('kitItemLineItems', $resultData);
        self::assertArrayHasKey('product', $resultData['kitItemLineItems'][0]);
        self::assertArrayHasKey('quantity', $resultData['kitItemLineItems'][0]);
        self::assertArrayHasKey('sortOrder', $resultData['kitItemLineItems'][0]);
    }

    public function testHandleDrySubmitTriggerOnPreSubmitWithKitItemLineItemQuantityWhenPriceIsManuallyChanged(): void
    {
        $data = [
            'drySubmitTrigger' => 'kitItemLineItems[0][quantity]',
            'price' => ['value' => 10.00, 'currency' => 'USD', 'is_price_changed' => true],
            'kitItemLineItems' => [
                0 => [
                    'product' => 123,
                    'quantity' => 5,
                    'price' => ['value' => 5.00, 'currency' => 'USD'],
                ],
            ],
        ];

        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, $data);

        $this->listener->handleDrySubmitTriggerOnPreSubmit($event);

        $resultData = $event->getData();

        // Price value should NOT be unset when is_price_changed is true (manually set price)
        self::assertArrayHasKey('price', $resultData);
        self::assertArrayHasKey('value', $resultData['price']);
        self::assertArrayHasKey('currency', $resultData['price']);
        self::assertArrayHasKey('is_price_changed', $resultData['price']);
        self::assertEquals(['value' => 10.00, 'currency' => 'USD', 'is_price_changed' => true], $resultData['price']);

        // Kit item line items should remain unchanged
        self::assertArrayHasKey('product', $resultData['kitItemLineItems'][0]);
        self::assertArrayHasKey('quantity', $resultData['kitItemLineItems'][0]);
        self::assertArrayHasKey('price', $resultData['kitItemLineItems'][0]);
    }

    public function testHandleDrySubmitTriggerOnPreSubmitWithKitItemLineItemPriceWhenPriceIsManuallyChanged(): void
    {
        $data = [
            'drySubmitTrigger' => 'kitItemLineItems[0][price]',
            'price' => ['value' => 20.00, 'currency' => 'USD', 'is_price_changed' => true],
            'kitItemLineItems' => [
                0 => [
                    'product' => 111,
                    'quantity' => 7,
                    'price' => ['value' => 15.00, 'currency' => 'EUR'],
                ],
            ],
        ];

        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, $data);

        $this->listener->handleDrySubmitTriggerOnPreSubmit($event);

        $resultData = $event->getData();

        // Price value should NOT be unset when is_price_changed is true (manually set price)
        self::assertArrayHasKey('price', $resultData);
        self::assertArrayHasKey('value', $resultData['price']);
        self::assertArrayHasKey('currency', $resultData['price']);
        self::assertArrayHasKey('is_price_changed', $resultData['price']);
        self::assertEquals(['value' => 20.00, 'currency' => 'USD', 'is_price_changed' => true], $resultData['price']);

        // Kit item line items should remain unchanged
        self::assertArrayHasKey('product', $resultData['kitItemLineItems'][0]);
        self::assertArrayHasKey('quantity', $resultData['kitItemLineItems'][0]);
        self::assertArrayHasKey('price', $resultData['kitItemLineItems'][0]);
    }

    public function testHandleDrySubmitTriggerOnPreSubmitWithKitItemLineItemProductWhenPriceIsManuallyChanged(): void
    {
        $data = [
            'drySubmitTrigger' => 'kitItemLineItems[0][product]',
            'price' => ['value' => 10.00, 'currency' => 'USD', 'is_price_changed' => true],
            'kitItemLineItems' => [
                0 => [
                    'product' => 123,
                    'quantity' => 5,
                    'price' => ['value' => 10.50, 'currency' => 'USD'],
                ],
            ],
        ];

        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, $data);

        $this->listener->handleDrySubmitTriggerOnPreSubmit($event);

        $resultData = $event->getData();

        // Price value should NOT be unset when is_price_changed is true (manually set price)
        self::assertArrayHasKey('price', $resultData);
        self::assertArrayHasKey('value', $resultData['price']);
        self::assertArrayHasKey('currency', $resultData['price']);
        self::assertArrayHasKey('is_price_changed', $resultData['price']);
        self::assertEquals(['value' => 10.00, 'currency' => 'USD', 'is_price_changed' => true], $resultData['price']);

        // Kit item quantity and price should still be unset
        self::assertArrayHasKey('product', $resultData['kitItemLineItems'][0]);
        self::assertArrayNotHasKey('quantity', $resultData['kitItemLineItems'][0]);
        self::assertArrayNotHasKey('price', $resultData['kitItemLineItems'][0]);
    }
}
