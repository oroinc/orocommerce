<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Async;

use Oro\Bundle\CheckoutBundle\Async\RecalculateCheckoutSubtotalsMessageFilter;
use Oro\Bundle\CheckoutBundle\Async\Topic\RecalculateCheckoutSubtotalsTopic;
use Oro\Bundle\MessageQueueBundle\Client\MessageBuffer;

class RecalculateCheckoutSubtotalsMessageFilterTest extends \PHPUnit\Framework\TestCase
{
    private RecalculateCheckoutSubtotalsMessageFilter $filter;

    protected function setUp(): void
    {
        $this->filter = new RecalculateCheckoutSubtotalsMessageFilter();
    }

    public function testApplyEmptyBuffer(): void
    {
        $buffer = new MessageBuffer();
        $this->filter->apply($buffer);

        self::assertEquals([], $buffer->getMessages());
    }

    public function testApplyNoSubtotalMessages(): void
    {
        $buffer = new MessageBuffer();
        $buffer->addMessage('test', 'message');
        $this->filter->apply($buffer);

        self::assertEquals([['test', 'message']], $buffer->getMessages());
    }

    public function testApply(): void
    {
        $buffer = new MessageBuffer();
        $buffer->addMessage('test', 'message1');
        $buffer->addMessage('test', 'message2');
        $buffer->addMessage(RecalculateCheckoutSubtotalsTopic::getName(), '');
        $buffer->addMessage(RecalculateCheckoutSubtotalsTopic::getName(), '');

        $this->filter->apply($buffer);

        self::assertEquals(
            [['test', 'message1'], ['test', 'message2'], [RecalculateCheckoutSubtotalsTopic::getName(), '']],
            $buffer->getMessages()
        );
    }
}
