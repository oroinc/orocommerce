<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\OrderBundle\Async\Topic\OrderDraftsCleanupTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

final class OrderDraftsCleanupTopicTest extends AbstractTopicTestCase
{
    #[\Override]
    protected function getTopic(): TopicInterface
    {
        return new OrderDraftsCleanupTopic();
    }

    #[\Override]
    public function validBodyDataProvider(): array
    {
        return [
            'empty body with default' => [
                'body' => [],
                'expectedBody' => [
                    'draftLifetimeDays' => 7,
                ],
            ],
            'with custom draftLifetimeDays' => [
                'body' => [
                    'draftLifetimeDays' => 30,
                ],
                'expectedBody' => [
                    'draftLifetimeDays' => 30,
                ],
            ],
            'with 1 day' => [
                'body' => [
                    'draftLifetimeDays' => 1,
                ],
                'expectedBody' => [
                    'draftLifetimeDays' => 1,
                ],
            ],
            'with 90 days' => [
                'body' => [
                    'draftLifetimeDays' => 90,
                ],
                'expectedBody' => [
                    'draftLifetimeDays' => 90,
                ],
            ],
        ];
    }

    #[\Override]
    public function invalidBodyDataProvider(): array
    {
        return [
            'draftLifetimeDays is string' => [
                'body' => [
                    'draftLifetimeDays' => '7',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "draftLifetimeDays" with value "7" is expected '
                    . 'to be of type "int", but is of type "string"./',
            ],
            'draftLifetimeDays is null' => [
                'body' => [
                    'draftLifetimeDays' => null,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "draftLifetimeDays" with value null is expected '
                    . 'to be of type "int", but is of type "null"./',
            ],
            'draftLifetimeDays is float' => [
                'body' => [
                    'draftLifetimeDays' => 7.5,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "draftLifetimeDays" with value 7.5 is expected '
                    . 'to be of type "int", but is of type "float|double"./',
            ],
            'draftLifetimeDays is array' => [
                'body' => [
                    'draftLifetimeDays' => [7],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "draftLifetimeDays" with value array is expected '
                    . 'to be of type "int", but is of type "array"./',
            ],
        ];
    }

    public function testGetName(): void
    {
        self::assertEquals('oro.order.draft_session.cleanup.order_draft', $this->getTopic()::getName());
    }

    public function testGetDescription(): void
    {
        self::assertEquals(
            'Deletes outdated draft orders and order line items',
            $this->getTopic()::getDescription()
        );
    }
}
