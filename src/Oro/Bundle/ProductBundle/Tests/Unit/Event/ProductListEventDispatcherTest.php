<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Event;

use Oro\Bundle\ProductBundle\Event\ProductListEvent;
use Oro\Bundle\ProductBundle\Event\ProductListEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\Event;

class ProductListEventDispatcherTest extends \PHPUnit\Framework\TestCase
{
    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var ProductListEventDispatcher */
    private $productListEventDispatcher;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->productListEventDispatcher = new ProductListEventDispatcher($this->eventDispatcher);
    }

    public function testDispatch()
    {
        $productListType = 'test_list';
        $event = $this->createMock(ProductListEvent::class);
        $event->expects(self::once())
            ->method('getProductListType')
            ->willReturn($productListType);

        $eventName = 'test_event_name';

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$event, $eventName],
                [$event, $eventName . '.' . $productListType]
            );

        $this->productListEventDispatcher->dispatch($event, $eventName);
    }

    public function testDispatchForInvalidEvent()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Unexpected event type. Expected instance of %s.',
            ProductListEvent::class
        ));

        $this->productListEventDispatcher->dispatch($this->createMock(Event::class), 'test_event_name');
    }

    public function testDispatchWhenEventNameIsNotSpecified()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The event name must not be empty.');

        $this->productListEventDispatcher->dispatch($this->createMock(ProductListEvent::class));
    }

    public function testDispatchWhenEventNameIsEmpty()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The event name must not be empty.');

        $this->productListEventDispatcher->dispatch($this->createMock(ProductListEvent::class), '');
    }
}
