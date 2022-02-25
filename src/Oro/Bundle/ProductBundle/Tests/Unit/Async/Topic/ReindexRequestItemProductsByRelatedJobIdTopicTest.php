<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\ProductBundle\Async\Topic\ReindexRequestItemProductsByRelatedJobIdTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ReindexRequestItemProductsByRelatedJobIdTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new ReindexRequestItemProductsByRelatedJobIdTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'all options' => [
                'body' => [
                    'relatedJobId' => 1,
                ],
                'expectedBody' => [
                    'relatedJobId' => 1,
                ],
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required option "relatedJobId" is missing./',
            ]
        ];
    }
}
