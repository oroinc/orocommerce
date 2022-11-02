<?php

declare(strict_types=1);

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\RedirectBundle\Async\Topic\SyncSlugRedirectsTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class SyncSlugRedirectsTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new SyncSlugRedirectsTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'slugId is of type int' => [
                'body' => [SyncSlugRedirectsTopic::SLUG_ID => 42],
                'expectedBody' => [SyncSlugRedirectsTopic::SLUG_ID => 42],
            ],
            'slugId is of type string' => [
                'body' => [SyncSlugRedirectsTopic::SLUG_ID => '42'],
                'expectedBody' => [SyncSlugRedirectsTopic::SLUG_ID => '42'],
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'empty body' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required option "slugId" is missing./',
            ],
            'slugId has invalid type' => [
                'body' => ['slugId' => new \stdClass()],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "slugId" with value stdClass is expected '
                    . 'to be of type "int" or "string"/',
            ],
        ];
    }
}
