<?php

declare(strict_types=1);

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\VisibilityBundle\Async\Topic\VisibilityOnChangeProductCategoryTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class VisibilityOnChangeProductCategoryTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new VisibilityOnChangeProductCategoryTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'required only' => [
                'body' => ['id' => 42],
                'expectedBody' => ['id' => 42, 'scheduleReindex' => false],
            ],
            'all defined' => [
                'body' => ['id' => 42, 'scheduleReindex' => true],
                'expectedBody' => ['id' => 42, 'scheduleReindex' => true],
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
            'scheduleReindex has invalid type' => [
                'body' => ['id' => 42, 'scheduleReindex' => new \stdClass()],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "scheduleReindex" with value stdClass is expected '
                    . 'to be of type "bool"/',
            ],
        ];
    }
}
