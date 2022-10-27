<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Event;

use Oro\Bundle\ShoppingListBundle\Event\LineItemValidateEvent;

class LineItemValidateEventTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var |\PHPUnit\Framework\MockObject\MockObject
     */
    protected $lineItems;

    /**
     * @var LineItemValidateEvent
     */
    protected $lineItemValidateEvent;

    protected function setUp(): void
    {
        $context = [];
        $this->lineItemValidateEvent = new LineItemValidateEvent($this->lineItems, $context);
    }

    public function testAddErrorByUnit()
    {
        $this->lineItemValidateEvent->addErrorByUnit('testSku', 'item', 'testMessage');
        $errors = $this->lineItemValidateEvent->getErrors();
        $this->assertCount(1, $errors);
        $this->assertArrayHasKey('sku', $errors[0]);
        $this->assertEquals($errors[0]['sku'], 'testSku');
        $this->assertArrayHasKey('unit', $errors[0]);
        $this->assertEquals($errors[0]['unit'], 'item');
        $this->assertArrayHasKey('message', $errors[0]);
        $this->assertEquals($errors[0]['message'], 'testMessage');
    }

    public function testHasErrors()
    {
        $this->assertFalse($this->lineItemValidateEvent->hasErrors());
        $this->lineItemValidateEvent->addErrorByUnit('testSku', 'item', 'testMessage');
        $this->assertTrue($this->lineItemValidateEvent->hasErrors());
    }
}
