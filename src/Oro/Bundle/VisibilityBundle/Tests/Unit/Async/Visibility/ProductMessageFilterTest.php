<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Async\Visibility;

use Oro\Bundle\MessageQueueBundle\Client\MessageBuffer;
use Oro\Bundle\VisibilityBundle\Async\Visibility\ProductMessageFilter;

class ProductMessageFilterTest extends \PHPUnit\Framework\TestCase
{
    private const TOPIC = 'test_topic';

    /** @var ProductMessageFilter */
    private $filter;

    protected function setUp()
    {
        $this->filter = new ProductMessageFilter(self::TOPIC);
    }

    public function testApplyForEmptyBuffer()
    {
        $buffer = new MessageBuffer();
        $this->filter->apply($buffer);
        $this->assertEquals([], $buffer->getMessages());
    }

    public function testApply()
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
                0 => [self::TOPIC, ['id' => 42]],
                2 => [self::TOPIC, ['id' => 123]],
                3 => [self::TOPIC, ['id' => 321]]
            ],
            $buffer->getMessages()
        );
    }
}
