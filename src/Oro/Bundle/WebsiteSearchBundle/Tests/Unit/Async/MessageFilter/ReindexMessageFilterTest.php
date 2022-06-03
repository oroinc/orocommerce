<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Async\MessageFilter;

use Oro\Bundle\MessageQueueBundle\Client\MessageBuffer;
use Oro\Bundle\WebsiteSearchBundle\Async\MessageFilter\ReindexMessageFilter;
use Oro\Component\MessageQueue\Client\Message;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ReindexMessageFilterTest extends \PHPUnit\Framework\TestCase
{
    private const TOPIC = 'test_topic';

    /** @var ReindexMessageFilter */
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

        $this->assertEqualsCanonicalizing(
            [
                [self::TOPIC, []],
            ],
            $buffer->getMessages()
        );
    }

    public function testApplyWhenAtLeastOneWithNoEntityIds(): void
    {
        $buffer = new MessageBuffer();

        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [1, 2]]]);
        $buffer->addMessage(self::TOPIC, []);

        $this->filter->apply($buffer);

        $this->assertEqualsCanonicalizing(
            [
                [self::TOPIC, []],
            ],
            $buffer->getMessages()
        );
    }

    public function testApplyLimitedIdsWithFieldGroups(): void
    {
        $buffer = new MessageBuffer();

        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [1, 2], 'fieldGroups' => ['main']]]);
        $buffer->addMessage(self::TOPIC, []);

        $this->filter->apply($buffer);

        $this->assertEqualsCanonicalizing(
            [
                [self::TOPIC, []],
            ],
            $buffer->getMessages()
        );
    }

    public function testApplyLimitedIdsFullAllWithFieldGroups(): void
    {
        $buffer = new MessageBuffer();

        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [1, 2]]]);
        $buffer->addMessage(self::TOPIC, ['context' => ['fieldGroups' => ['main']]]);

        $this->filter->apply($buffer);

        $this->assertEqualsCanonicalizing(
            [
                [self::TOPIC, ['context' => ['fieldGroups' => ['main']]]],
                [self::TOPIC, ['context' => ['entityIds' => [1, 2]]]],
            ],
            $buffer->getMessages()
        );
    }

    public function testApplyLimitedIdsFieldGroupAllWithFieldGroups(): void
    {
        $buffer = new MessageBuffer();

        $buffer->addMessage(self::TOPIC, ['context' => ['fieldGroups' => ['main']]]);
        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [1, 2], 'fieldGroups' => ['main', 'test']]]);

        $this->filter->apply($buffer);

        $this->assertEqualsCanonicalizing(
            [
                [self::TOPIC, ['context' => ['fieldGroups' => ['main']]]],
                [self::TOPIC, ['context' => ['entityIds' => [1, 2], 'fieldGroups' => ['test']]]],
            ],
            $buffer->getMessages()
        );
    }

    public function testApplyLimitedIdsFieldGroupAllWithFieldGroupsFirstIds(): void
    {
        $buffer = new MessageBuffer();

        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [1, 2], 'fieldGroups' => ['main', 'test']]]);
        $buffer->addMessage(self::TOPIC, ['context' => ['fieldGroups' => ['main']]]);

        $this->filter->apply($buffer);

        $this->assertEqualsCanonicalizing(
            [
                [self::TOPIC, ['context' => ['entityIds' => [1, 2], 'fieldGroups' => ['test']]]],
                [self::TOPIC, ['context' => ['fieldGroups' => ['main']]]],
            ],
            $buffer->getMessages()
        );
    }

    public function testApplyLimitedIdsFieldGroupAllWithSameFieldGroupsIds(): void
    {
        $buffer = new MessageBuffer();

        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [1, 2], 'fieldGroups' => ['main']]]);
        $buffer->addMessage(self::TOPIC, ['context' => ['fieldGroups' => ['main']]]);

        $this->filter->apply($buffer);

        $this->assertEqualsCanonicalizing(
            [
                [self::TOPIC, ['context' => ['fieldGroups' => ['main']]]],
            ],
            $buffer->getMessages()
        );
    }

    public function testApplyNoEntityIdsIdsWithFieldGroups(): void
    {
        $buffer = new MessageBuffer();

        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [], 'fieldGroups' => ['main']]]);
        $buffer->addMessage(self::TOPIC, ['context' => ['fieldGroups' => ['test']]]);

        $this->filter->apply($buffer);

        $this->assertEqualsCanonicalizing(
            [
                [self::TOPIC, ['context' => ['fieldGroups' => ['main', 'test']]]],
            ],
            $buffer->getMessages()
        );
    }

    public function testApplyWhenDuplicatedEntityIdsNoFieldGroups(): void
    {
        $buffer = new MessageBuffer();

        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [1]]]);
        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [1]]]);
        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [2]]]);
        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [2]]]);
        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [3]]]);
        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [3]]]);

        $this->filter->apply($buffer);

        $this->assertEqualsCanonicalizing(
            [
                [self::TOPIC, ['context' => ['entityIds' => [1, 2, 3]]]],
            ],
            $buffer->getMessages()
        );
    }

    public function testApplyWhenDuplicatedEntityIdsSameFieldGroup(): void
    {
        $buffer = new MessageBuffer();

        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [1], 'fieldGroups' => ['main']]]);
        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [1], 'fieldGroups' => ['main']]]);
        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [2], 'fieldGroups' => ['main']]]);
        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [2], 'fieldGroups' => ['main']]]);
        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [3], 'fieldGroups' => ['main']]]);
        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [3], 'fieldGroups' => ['main']]]);

        $this->filter->apply($buffer);

        $this->assertEqualsCanonicalizing(
            [
                [self::TOPIC, ['context' => ['entityIds' => [1, 2, 3], 'fieldGroups' => ['main']]]],
            ],
            $buffer->getMessages()
        );
    }

    public function testApplyWhenDuplicatedEntityIdsDifferentFieldGroups(): void
    {
        $buffer = new MessageBuffer();

        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [1], 'fieldGroups' => ['main']]]);
        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [1], 'fieldGroups' => ['main']]]);
        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [2], 'fieldGroups' => ['main']]]);
        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [2], 'fieldGroups' => ['test']]]);
        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [3], 'fieldGroups' => ['main']]]);
        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [4, 5], 'fieldGroups' => ['main']]]);
        $buffer->addMessage(self::TOPIC, ['context' => ['entityIds' => [3]]]);

        $this->filter->apply($buffer);

        $this->assertEqualsCanonicalizing(
            [
                [self::TOPIC, ['context' => ['entityIds' => [1, 4, 5], 'fieldGroups' => ['main']]]],
                [self::TOPIC, ['context' => ['entityIds' => [2], 'fieldGroups' => ['main', 'test']]]],
                [self::TOPIC, ['context' => ['entityIds' => [3]]]],
            ],
            $buffer->getMessages()
        );
    }

    public function testApplyWhenDuplicatedEntityIdsDifferentFieldGroupsForMessageObject(): void
    {
        $buffer = new MessageBuffer();

        $buffer->addMessage(self::TOPIC, new Message(['context' => ['entityIds' => [1], 'fieldGroups' => ['main']]]));
        $buffer->addMessage(self::TOPIC, new Message(['context' => ['entityIds' => [1], 'fieldGroups' => ['main']]]));
        $buffer->addMessage(self::TOPIC, new Message(['context' => ['entityIds' => [2], 'fieldGroups' => ['main']]]));
        $buffer->addMessage(self::TOPIC, new Message(['context' => ['entityIds' => [2], 'fieldGroups' => ['test']]]));
        $buffer->addMessage(self::TOPIC, new Message(['context' => ['entityIds' => [3], 'fieldGroups' => ['main']]]));
        $buffer->addMessage(
            self::TOPIC,
            new Message(['context' => ['entityIds' => [4, 5], 'fieldGroups' => ['main']]])
        );
        $buffer->addMessage(self::TOPIC, new Message(['context' => ['entityIds' => [3]]]));

        $this->filter->apply($buffer);

        $this->assertEqualsCanonicalizing(
            [
                [self::TOPIC, new Message(['context' => ['entityIds' => [1, 4, 5], 'fieldGroups' => ['main']]])],
                [self::TOPIC, new Message(['context' => ['entityIds' => [2], 'fieldGroups' => ['main', 'test']]])],
                [self::TOPIC, new Message(['context' => ['entityIds' => [3]]])]
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

        $this->assertEqualsCanonicalizing(
            [
                [self::TOPIC, ['class' => 'SampleClass1', 'context' => ['entityIds' => [1, 2, 3]]]],
                [self::TOPIC, ['class' => 'SampleClass2', 'context' => ['entityIds' => [1, 2, 3]]]],
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

        $this->assertEqualsCanonicalizing(
            [
                [
                    self::TOPIC,
                    ['class' => 'SampleClass1', 'granulize' => true, 'context' => ['entityIds' => [1, 2, 3]]],
                ],
                [
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

        $this->assertEqualsCanonicalizing(
            [
                [
                    self::TOPIC,
                    [
                        'class' => 'SampleClass1',
                        'granulize' => true,
                        'context' => ['websiteIds' => [1], 'entityIds' => [1, 2, 3]],
                    ],
                ],
                [
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

        $this->assertEqualsCanonicalizing(
            [
                [
                    self::TOPIC,
                    [
                        'class' => 'SampleClass1',
                        'granulize' => true,
                        'context' => ['websiteIds' => [1], 'entityIds' => [1]],
                        'jobId' => 199,
                    ],
                ],
                [
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

        $this->assertEqualsCanonicalizing(
            [
                [self::TOPIC, ['jobId' => $jobId1, 'context' => ['entityIds' => [1]]]],
                [self::TOPIC, ['jobId' => $jobId2, 'context' => ['entityIds' => [2, 4]]]],
                [self::TOPIC, ['context' => ['entityIds' => [1, 2, 3]]]],
            ],
            $buffer->getMessages()
        );
    }
}
