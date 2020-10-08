<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Event;

use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Event\LineItemDataBuildEvent;

class LineItemDataBuildEventTest extends \PHPUnit\Framework\TestCase
{
    /** @var LineItem */
    private $lineItem1;

    /** @var LineItem */
    private $lineItem2;

    /** @var LineItem */
    private $lineItem3;

    /** @var LineItemDataBuildEvent */
    private $event;

    protected function setUp(): void
    {
        $this->lineItem1 = $this->createMock(LineItem::class);
        $this->lineItem2 = $this->createMock(LineItem::class);
        $this->lineItem3 = $this->createMock(LineItem::class);

        $this->event = new LineItemDataBuildEvent([$this->lineItem1, $this->lineItem2, $this->lineItem3], ['context']);
    }

    public function testGetContext(): void
    {
        $this->assertSame(['context'], $this->event->getContext());
    }

    public function testGetLineItems(): void
    {
        $this->assertSame([$this->lineItem1, $this->lineItem2, $this->lineItem3], $this->event->getLineItems());
    }

    public function testDataForLineItem(): void
    {
        $this->assertEquals([], $this->event->getDataForLineItem(42));

        $this->event->addDataForLineItem(42, 'name', 'value1');
        $this->assertEquals(['name' => 'value1'], $this->event->getDataForLineItem(42));

        $this->event->addDataForLineItem(42, 'name', 'value2');
        $this->assertEquals(['name' => 'value2'], $this->event->getDataForLineItem(42));

        $this->event->setDataForLineItem(42, ['name2' => 'value2', 'name3' => 'value3']);
        $this->assertEquals(['name2' => 'value2', 'name3' => 'value3'], $this->event->getDataForLineItem(42));
    }
}
