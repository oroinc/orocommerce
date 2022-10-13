<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\ShoppingListBundle\Async\Topic\InvalidateTotalsByInventoryStatusPerWebsiteTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class InvalidateTotalsByInventoryStatusPerWebsiteTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new InvalidateTotalsByInventoryStatusPerWebsiteTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'required' => [
                'body' => [
                    'context' => ['class' => 'test', 'id' => 1],
                ],
                'expectedBody' => [
                    'context' => ['class' => 'test', 'id' => 1],
                ],
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'WithoutContextParameters' => [
                'body' => [
                    'context' => [],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "context" is expected to contain "id" and "class" options./',
            ],
            'WithWrongContextParameterType' => [
                'body' => [
                    'context' => 1,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "context" with value 1 ' .
                    'is expected to be of type "array", but is of type "int"./',
            ],
        ];
    }
}
