<?php

declare(strict_types=1);

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\VisibilityBundle\Async\Topic\VisibilityOnChangeCustomerTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class VisibilityOnChangeCustomerTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new VisibilityOnChangeCustomerTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'required only' => [
                'body' => ['id' => 42],
                'expectedBody' => ['id' => 42],
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required option "id" is missing./',
            ],
            'id has invalid type' => [
                'body' => ['id' => new \stdClass()],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "id" with value stdClass is expected to be of type "int"/',
            ],
        ];
    }
}
