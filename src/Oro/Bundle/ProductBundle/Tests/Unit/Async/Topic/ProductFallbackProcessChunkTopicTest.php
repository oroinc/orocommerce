<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\ProductBundle\Async\Topic\ProductFallbackProcessChunkTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

final class ProductFallbackProcessChunkTopicTest extends AbstractTopicTestCase
{
    #[\Override]
    protected function getTopic(): TopicInterface
    {
        return new ProductFallbackProcessChunkTopic();
    }

    #[\Override]
    public function validBodyDataProvider(): array
    {
        return [
            'required options' => [
                'body' => [
                    ProductFallbackProcessChunkTopic::JOB_ID => 123,
                    ProductFallbackProcessChunkTopic::PRODUCT_IDS => [1, 2, 3],
                ],
                'expectedBody' => [
                    ProductFallbackProcessChunkTopic::JOB_ID => 123,
                    ProductFallbackProcessChunkTopic::PRODUCT_IDS => [1, 2, 3],
                ],
            ],
            'normalizes product_ids to integers' => [
                'body' => [
                    ProductFallbackProcessChunkTopic::JOB_ID => 456,
                    ProductFallbackProcessChunkTopic::PRODUCT_IDS => ['10', '20', '30'],
                ],
                'expectedBody' => [
                    ProductFallbackProcessChunkTopic::JOB_ID => 456,
                    ProductFallbackProcessChunkTopic::PRODUCT_IDS => [10, 20, 30],
                ],
            ],
            'large product_ids array' => [
                'body' => [
                    ProductFallbackProcessChunkTopic::JOB_ID => 789,
                    ProductFallbackProcessChunkTopic::PRODUCT_IDS => range(1, 500),
                ],
                'expectedBody' => [
                    ProductFallbackProcessChunkTopic::JOB_ID => 789,
                    ProductFallbackProcessChunkTopic::PRODUCT_IDS => range(1, 500),
                ],
            ],
        ];
    }

    #[\Override]
    public function invalidBodyDataProvider(): array
    {
        return [
            'empty body' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required options "job_id", "product_ids" are missing./',
            ],
            'missing job_id' => [
                'body' => [
                    ProductFallbackProcessChunkTopic::PRODUCT_IDS => [1, 2, 3],
                ],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required option "job_id" is missing./',
            ],
            'missing product_ids' => [
                'body' => [
                    ProductFallbackProcessChunkTopic::JOB_ID => 123,
                ],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required option "product_ids" is missing./',
            ],
            'invalid job_id type' => [
                'body' => [
                    ProductFallbackProcessChunkTopic::JOB_ID => 'invalid',
                    ProductFallbackProcessChunkTopic::PRODUCT_IDS => [1, 2, 3],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "job_id" with value "invalid" ' .
                    'is expected to be of type "int", but is of type "string"./',
            ],
            'invalid product_ids type' => [
                'body' => [
                    ProductFallbackProcessChunkTopic::JOB_ID => 123,
                    ProductFallbackProcessChunkTopic::PRODUCT_IDS => 'invalid',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "product_ids" with value "invalid" ' .
                    'is expected to be of type "array", but is of type "string"./',
            ],
            'empty product_ids array' => [
                'body' => [
                    ProductFallbackProcessChunkTopic::JOB_ID => 123,
                    ProductFallbackProcessChunkTopic::PRODUCT_IDS => [],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The product_ids option must contain at least one identifier./',
            ],
        ];
    }

    public function testGetName(): void
    {
        self::assertSame('oro.platform.post_upgrade.process_chunk', ProductFallbackProcessChunkTopic::getName());
    }

    public function testGetDescription(): void
    {
        self::assertSame(
            'Processes a chunk of products to populate fallback values.',
            $this->getTopic()->getDescription()
        );
    }
}
