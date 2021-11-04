<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Async\MessageFilter;

use Oro\Bundle\MessageQueueBundle\Client\MessageBuffer;
use Oro\Bundle\VisibilityBundle\Async\Visibility\ProductMessageFilter;
use Oro\Bundle\WebsiteSearchBundle\Async\MessageFilter\ReindexMessageFilter;

class ReindexMessageFilterTest extends \PHPUnit\Framework\TestCase
{
    private const TOPIC = 'test_topic';

    /** @var ProductMessageFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->filter = new ReindexMessageFilter(self::TOPIC);
    }

    public function testApplyForEmptyBuffer(): void
    {
        $buffer = new MessageBuffer();
        $this->filter->apply($buffer);
        $this->assertEquals([], $buffer->getMessages());
    }

    public function testApplyWhenNoEntityIds(): void
    {
        $buffer = new MessageBuffer();

        $buffer->addMessage(self::TOPIC, []);
        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => []]]);

        $this->filter->apply($buffer);

        $this->assertEquals(
            [
                0 => [self::TOPIC, ['context' => ['entityIds' => []]]],
            ],
            $buffer->getMessages()
        );
    }

    public function testApplyWhenDuplicatedEntityIds(): void
    {
        $buffer = new MessageBuffer();

        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [1]]]);
        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [1]]]);
        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [2]]]);
        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [2]]]);
        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [3]]]);
        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [3]]]);

        $this->filter->apply($buffer);

        $this->assertEquals(
            [
                0 => [self::TOPIC, ['context' => ['entityIds' => [1, 2, 3]]]],
            ],
            $buffer->getMessages()
        );
    }

    public function testApplyWhenDifferentClassesEntityIds(): void
    {
        $buffer = new MessageBuffer();

        // add same message twice
        $buffer->addMessage(self::TOPIC, ['class' => 'SampleClass1', 'context' => ['entityIds' => [1]]]);
        $buffer->addMessage(self::TOPIC, ['class' => 'SampleClass2', 'context' => ['entityIds' => [1]]]);
        $buffer->addMessage(self::TOPIC, ['class' => 'SampleClass1', 'context' => ['entityIds' => [2]]]);
        $buffer->addMessage(self::TOPIC, ['class' => 'SampleClass2', 'context' => ['entityIds' => [2]]]);
        $buffer->addMessage(self::TOPIC, ['class' => 'SampleClass1', 'context' => ['entityIds' => [3]]]);
        $buffer->addMessage(self::TOPIC, ['class' => 'SampleClass2', 'context' => ['entityIds' => [3]]]);

        $this->filter->apply($buffer);

        $this->assertEquals(
            [
                0 => [self::TOPIC, ['class' => 'SampleClass1', 'context' => ['entityIds' => [1, 2, 3]]]],
                1 => [self::TOPIC, ['class' => 'SampleClass2', 'context' => ['entityIds' => [1, 2, 3]]]],
            ],
            $buffer->getMessages()
        );
    }

    public function testApplyWhenGranulize(): void
    {
        $buffer = new MessageBuffer();

        $buffer->addMessage(
            self::TOPIC,
            ['class' => 'SampleClass1', 'granulize' => true, 'context' => ['entityIds' => [1]]]
        );
        $buffer->addMessage(
            self::TOPIC,
            ['class' => 'SampleClass1', 'granulize' => false, 'context' => ['entityIds' => [1]]]
        );
        $buffer->addMessage(
            self::TOPIC,
            ['class' => 'SampleClass1', 'granulize' => true, 'context' => ['entityIds' => [2]]]
        );
        $buffer->addMessage(
            self::TOPIC,
            ['class' => 'SampleClass1', 'granulize' => false, 'context' => ['entityIds' => [2]]]
        );
        $buffer->addMessage(
            self::TOPIC,
            ['class' => 'SampleClass1', 'granulize' => true, 'context' => ['entityIds' => [3]]]
        );
        $buffer->addMessage(
            self::TOPIC,
            ['class' => 'SampleClass1', 'granulize' => false, 'context' => ['entityIds' => [3]]]
        );

        $this->filter->apply($buffer);

        $this->assertEquals(
            [
                0 => [
                    self::TOPIC,
                    ['class' => 'SampleClass1', 'granulize' => true, 'context' => ['entityIds' => [1, 2, 3]]],
                ],
                1 => [
                    self::TOPIC,
                    ['class' => 'SampleClass1', 'granulize' => false, 'context' => ['entityIds' => [1, 2, 3]]],
                ],
            ],
            $buffer->getMessages()
        );
    }

    public function testApplyWhenDifferentWebsiteIds(): void
    {
        $buffer = new MessageBuffer();

        $buffer->addMessage(
            self::TOPIC,
            ['class' => 'SampleClass1', 'granulize' => true, 'context' => ['websiteIds' => [1], 'entityIds' => [1]]]
        );
        $buffer->addMessage(
            self::TOPIC,
            ['class' => 'SampleClass1', 'granulize' => true, 'context' => ['websiteIds' => [2], 'entityIds' => [1]]]
        );
        $buffer->addMessage(
            self::TOPIC,
            ['class' => 'SampleClass1', 'granulize' => true, 'context' => ['websiteIds' => [1], 'entityIds' => [2]]]
        );
        $buffer->addMessage(
            self::TOPIC,
            ['class' => 'SampleClass1', 'granulize' => true, 'context' => ['websiteIds' => [2], 'entityIds' => [2]]]
        );
        $buffer->addMessage(
            self::TOPIC,
            ['class' => 'SampleClass1', 'granulize' => true, 'context' => ['websiteIds' => [1], 'entityIds' => [3]]]
        );
        $buffer->addMessage(
            self::TOPIC,
            ['class' => 'SampleClass1', 'granulize' => true, 'context' => ['websiteIds' => [2], 'entityIds' => [3]]]
        );

        $this->filter->apply($buffer);

        $this->assertEquals(
            [
                0 => [
                    self::TOPIC,
                    [
                        'class' => 'SampleClass1',
                        'granulize' => true,
                        'context' => ['websiteIds' => [1], 'entityIds' => [1, 2, 3]],
                    ],
                ],
                1 => [
                    self::TOPIC,
                    [
                        'class' => 'SampleClass1',
                        'granulize' => true,
                        'context' => ['websiteIds' => [2], 'entityIds' => [1, 2, 3]],
                    ],
                ],
            ],
            $buffer->getMessages()
        );
    }

    public function testDoNotAggregateMessagesForDifferentJobs()
    {
        $buffer = new MessageBuffer();

        $buffer->addMessage(
            self::TOPIC,
            [
                'class' => 'SampleClass1',
                'granulize' => true,
                'context' => ['websiteIds' => [1], 'entityIds' => [1]],
                'jobId' => 199,
            ]
        );
        $buffer->addMessage(
            self::TOPIC,
            [
                'class' => 'SampleClass1',
                'granulize' => true,
                'context' => ['websiteIds' => [2], 'entityIds' => [2]],
                'jobId' => 200,
            ]
        );

        $this->filter->apply($buffer);

        $this->assertEquals(
            [
                0 => [
                    self::TOPIC,
                    [
                        'class' => 'SampleClass1',
                        'granulize' => true,
                        'context' => ['websiteIds' => [1], 'entityIds' => [1]],
                        'jobId' => 199,
                    ],
                ],
                1 => [
                    self::TOPIC,
                    [
                        'class' => 'SampleClass1',
                        'granulize' => true,
                        'context' => ['websiteIds' => [2], 'entityIds' => [2]],
                        'jobId' => 200,
                    ],
                ],
            ],
            $buffer->getMessages()
        );
    }

    public function testApplyWhenJobId(): void
    {
        $buffer = new MessageBuffer();
        $jobId1 = 1;
        $jobId2 = 2;

        $buffer->addMessage(self::TOPIC, ['jobId' => $jobId1, 'context' => ['entityIds' => [1]]]);
        $buffer->addMessage(self::TOPIC, ['jobId' => $jobId2, 'context' => ['entityIds' => [2]]]);
        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [1]]]);
        $buffer->addMessage(self::TOPIC, ['jobId' => $jobId2, 'context' => ['entityIds' => [4]]]);
        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [2]]]);
        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [3]]]);
        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [3]]]);

        $this->filter->apply($buffer);

        $this->assertEquals(
            [
                [self::TOPIC, ['jobId' => $jobId1, 'context' => ['entityIds' => [1]]]],
                [self::TOPIC, ['jobId' => $jobId2, 'context' => ['entityIds' => [2, 4]]]],
                [self::TOPIC, ['context' => ['entityIds' => [1, 2, 3]]]],
            ],
            $buffer->getMessages()
        );
    }
}
