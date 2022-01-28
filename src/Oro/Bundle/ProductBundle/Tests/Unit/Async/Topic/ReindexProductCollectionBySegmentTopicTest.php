<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\ProductBundle\Async\Topic\ReindexProductCollectionBySegmentTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ReindexProductCollectionBySegmentTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new ReindexProductCollectionBySegmentTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'required only v1' => [
                'body' => [
                    'id' => 1,
                    'job_id' => 1,
                    'website_ids' => [1, 2, 3],
                    'is_full' => false
                ],
                'expectedBody' => [
                    'job_id' => 1,
                    'website_ids' => [1, 2, 3],
                    'is_full' => false,
                    'additional_products' => [],
                    'id' => 1,
                    'definition' => null
                ],
            ],
            'required only v2' => [
                'body' => [
                    'definition' => 'definition',
                    'job_id' => 1,
                    'website_ids' => [1, 2, 3],
                    'is_full' => false
                ],
                'expectedBody' => [
                    'job_id' => 1,
                    'website_ids' => [1, 2, 3],
                    'is_full' => false,
                    'additional_products' => [],
                    'id' => null,
                    'definition' => 'definition'
                ],
            ],
            'all options' => [
                'body' => [
                    'job_id' => 1,
                    'website_ids' => [1, 2, 3],
                    'is_full' => false,
                    'additional_products' => [2,3,4],
                    'id' => 111,
                    'definition' => 'some_definition'
                ],
                'expectedBody' => [
                    'job_id' => 1,
                    'website_ids' => [1, 2, 3],
                    'is_full' => false,
                    'additional_products' => [2,3,4],
                    'id' => 111,
                    'definition' => 'some_definition'
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
                'exceptionMessage' => '/The required options "is_full", "job_id", "website_ids" are missing./',
            ],
            '"id" and "definition" are not set' => [
                'body' => [
                    'job_id' => 1,
                    'website_ids' => [1, 2, 3],
                    'is_full' => false
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/One of these options "id" or "definition" must be present and has not null value./',
            ],
            'not set "id" and "definition" is null' => [
                'body' => [
                    'job_id' => 1,
                    'website_ids' => [1, 2, 3],
                    'is_full' => false,
                    'definition' => null
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/One of these options "id" or "definition" must be present and has not null value./',
            ],
            '"id" is null and "definition" is not set' => [
                'body' => [
                    'id' => null,
                    'job_id' => 1,
                    'website_ids' => [1, 2, 3],
                    'is_full' => false,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/One of these options "id" or "definition" must be present and has not null value./',
            ],
            '"id" and "definition" are null' => [
                'body' => [
                    'id' => null,
                    'job_id' => 1,
                    'website_ids' => [1, 2, 3],
                    'is_full' => false,
                    'definition' => null
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/One of these options "id" or "definition" must be present and has not null value./',
            ]
        ];
    }
}
