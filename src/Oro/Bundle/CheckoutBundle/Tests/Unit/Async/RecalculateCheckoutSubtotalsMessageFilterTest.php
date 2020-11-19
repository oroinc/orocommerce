<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Async;

use Oro\Bundle\CheckoutBundle\Async\RecalculateCheckoutSubtotalsMessageFilter;
use Oro\Bundle\CheckoutBundle\Async\Topics;
use Oro\Bundle\MessageQueueBundle\Client\MessageBuffer;

class RecalculateCheckoutSubtotalsMessageFilterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RecalculateCheckoutSubtotalsMessageFilter
     */
    private $filter;

    protected function setUp(): void
    {
        $this->filter = new RecalculateCheckoutSubtotalsMessageFilter();
    }

    public function testApplyEmptyBuffer()
    {
        $buffer = new MessageBuffer();
        $this->filter->apply($buffer);

        $this->assertEquals([], $buffer->getMessages());
    }

    public function testApplyNoSubtotalMessages()
    {
        $buffer = new MessageBuffer();
        $buffer->addMessage('test', 'message');
        $this->filter->apply($buffer);

        $this->assertEquals([['test', 'message']], $buffer->getMessages());
    }

    public function testApply()
    {
        $buffer = new MessageBuffer();
        $buffer->addMessage('test', 'message1');
        $buffer->addMessage('test', 'message2');
        $buffer->addMessage(Topics::RECALCULATE_CHECKOUT_SUBTOTALS, '');
        $buffer->addMessage(Topics::RECALCULATE_CHECKOUT_SUBTOTALS, '');

        $this->filter->apply($buffer);

        $this->assertEquals(
            [['test', 'message1'], ['test', 'message2'], [Topics::RECALCULATE_CHECKOUT_SUBTOTALS, '']],
            $buffer->getMessages()
        );
    }
}
