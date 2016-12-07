<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Event;

use Oro\Bundle\ShoppingListBundle\Event\LineItemValidateEvent;

class LineItemValidateEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var |\PHPUnit_Framework_MockObject_MockObject
     */
    protected $lineItems;

    /**
     * @var LineItemValidateEvent
     */
    protected $lineItemValidateEvent;

    protected function setUp()
    {
        $this->lineItemValidateEvent = new LineItemValidateEvent($this->lineItems);
    }

    public function testAddError()
    {
        $this->lineItemValidateEvent->addError('testSku', 'testMessage');
        $errors = $this->lineItemValidateEvent->getErrors();
        $this->assertCount(1, $errors);
        $this->assertArrayHasKey('sku', $errors[0]);
        $this->assertEquals($errors[0]['sku'], 'testSku');
        $this->assertArrayHasKey('message', $errors[0]);
        $this->assertEquals($errors[0]['message'], 'testMessage');
    }

    public function testHasErrors()
    {
        $this->assertFalse($this->lineItemValidateEvent->hasErrors());
        $this->lineItemValidateEvent->addError('testSku', 'testMessage');
        $this->assertTrue($this->lineItemValidateEvent->hasErrors());
    }
}
