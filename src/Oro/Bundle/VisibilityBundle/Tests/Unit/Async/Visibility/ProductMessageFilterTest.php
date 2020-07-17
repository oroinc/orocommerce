<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Async\Visibility;

use Oro\Bundle\MessageQueueBundle\Client\MessageBuffer;
use Oro\Bundle\VisibilityBundle\Async\Visibility\ProductMessageFilter;

class ProductMessageFilterTest extends \PHPUnit\Framework\TestCase
{
    private const TOPIC = 'test_topic';

    /** @var ProductMessageFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->filter = new ProductMessageFilter(self::TOPIC);
    }

    public function testApplyForEmptyBuffer(): void
    {
        $buffer = new MessageBuffer();
        $this->filter->apply($buffer);
        $this->assertEquals([], $buffer->getMessages());
    }

    public function testApplyWhenMultipleIds(): void
    {
        $buffer = new MessageBuffer();

        $buffer->addMessage(self::TOPIC, ['id' => 42]);
        $buffer->addMessage(self::TOPIC, ['id' => 123]);
        $buffer->addMessage(self::TOPIC, ['id' => 321]);

        $this->filter->apply($buffer);

        $this->assertEquals(
            [
                0 => [self::TOPIC, ['id' => [42, 123, 321], 'scheduleReindex' => true]],
            ],
            $buffer->getMessages()
        );
    }

    public function testApplyWhenMultipleIdsDuplicated(): void
    {
        $buffer = new MessageBuffer();

        // add same message twice
        $buffer->addMessage(self::TOPIC, ['id' => 42]);
        $buffer->addMessage(self::TOPIC, ['id' => 42]);

        $buffer->addMessage(self::TOPIC, ['id' => 123]);

        // add same message twice
        $buffer->addMessage(self::TOPIC, ['id' => 321]);
        $buffer->addMessage(self::TOPIC, ['id' => 321]);

        $this->filter->apply($buffer);

        $this->assertEquals(
            [
                0 => [self::TOPIC, ['id' => [42, 123, 321], 'scheduleReindex' => true]],
            ],
            $buffer->getMessages()
        );
    }

    public function testApplyWhenSingleIdDuplicated(): void
    {
        $buffer = new MessageBuffer();

        // add same message twice
        $buffer->addMessage(self::TOPIC, ['id' => 42]);
        $buffer->addMessage(self::TOPIC, ['id' => 42]);

        $this->filter->apply($buffer);

        $this->assertEquals(
            [
                0 => [self::TOPIC, ['id' => 42]],
            ],
            $buffer->getMessages()
        );
    }

    public function testApplyWhenSingleId(): void
    {
        $buffer = new MessageBuffer();

        // add same message twice
        $buffer->addMessage(self::TOPIC, ['id' => 42]);

        $this->filter->apply($buffer);

        $this->assertEquals(
            [
                0 => [self::TOPIC, ['id' => 42]],
            ],
            $buffer->getMessages()
        );
    }
}
