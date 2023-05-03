<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\ProductBundle\Async\Topic\ReindexRequestItemProductsByRelatedJobIdTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
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
            'required options' => [
                'body' => [
                    'relatedJobId' => 1,
                ],
                'expectedBody' => [
                    'relatedJobId' => 1,
                    'indexationFieldsGroups' => null
                ],
            ],
            'all options' => [
                'body' => [
                    'relatedJobId' => 1,
                    'indexationFieldsGroups' => ['main']
                ],
                'expectedBody' => [
                    'relatedJobId' => 1,
                    'indexationFieldsGroups' => ['main']
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
            ],
            'incorrect group' => [
                'body' => [
                    'relatedJobId' => 1,
                    'indexationFieldsGroups' => 'main'
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "indexationFieldsGroups" with value "main" is expected to be of ' .
                    'type "string\[\]" or "null", but is of type "string"\./',
            ]
        ];
    }

    public function testCreateJobName(): void
    {
        self::assertSame(
            'oro_product.reindex_request_item_products_by_related_job:42',
            $this->getTopic()->createJobName(['relatedJobId' => 42])
        );
    }
}
