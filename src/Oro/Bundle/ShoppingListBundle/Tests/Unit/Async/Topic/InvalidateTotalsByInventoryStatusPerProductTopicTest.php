<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\ShoppingListBundle\Async\Topic\InvalidateTotalsByInventoryStatusPerProductTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class InvalidateTotalsByInventoryStatusPerProductTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new InvalidateTotalsByInventoryStatusPerProductTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'required' => [
                'body' => [
                    'context' => ['class' => 'test', 'id' => 1],
                    'products' => [1, 2, 3],
                ],
                'expectedBody' => [
                    'context' => ['class' => 'test', 'id' => 1],
                    'products' => [1, 2, 3],
                ],
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'WithWrongContextParameterValue' => [
                'body' => [
                    'context' => [],
                    'products' => [1, 2, 3],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "context" is expected to contain "id" and "class" options./',
            ],
            'WithWrongContextParameterType' => [
                'body' => [
                    'context' => 1,
                    'products' => [1, 2, 3],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "context" with value 1 ' .
                    'is expected to be of type "array", but is of type "int"./',
            ],
            'WithWrongProductParameterType' => [
                'body' => [
                    'context' => ['class' => 'test', 'id' => 1],
                    'products' => 1,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "products" with value 1 ' .
                    'is expected to be of type "array", but is of type "int"./',
            ],
        ];
    }
}
