<?php

declare(strict_types=1);

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\VisibilityBundle\Async\Topic\VisibilityOnRemoveCategoryTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class VisibilityOnRemoveCategoryTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new VisibilityOnRemoveCategoryTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'expectedBody' => [],
            ],
            'with id' => [
                'body' => ['id' => 42],
                'expectedBody' => ['id' => 42],
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'id has invalid type' => [
                'body' => ['id' => new \stdClass()],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "id" with value stdClass is expected to be of type "int"/',
            ],
        ];
    }
}
