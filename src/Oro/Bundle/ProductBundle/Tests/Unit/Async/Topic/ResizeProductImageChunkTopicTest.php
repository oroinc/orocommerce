<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\ProductBundle\Async\Topic\ResizeProductImageChunkTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ResizeProductImageChunkTopicTest extends AbstractTopicTestCase
{
    #[\Override]
    protected function getTopic(): TopicInterface
    {
        return new ResizeProductImageChunkTopic();
    }

    #[\Override]
    public function validBodyDataProvider(): array
    {
        return [
            'required options only' => [
                'body' => [
                    ResizeProductImageChunkTopic::JOB_ID => 123,
                ],
                'expectedBody' => [
                    ResizeProductImageChunkTopic::JOB_ID => 123,
                    ResizeProductImageChunkTopic::FORCE => false,
                    ResizeProductImageChunkTopic::DIMENSIONS => null,
                    ResizeProductImageChunkTopic::IMAGE_IDS => null,
                ],
            ],
            'all options' => [
                'body' => [
                    ResizeProductImageChunkTopic::JOB_ID => 456,
                    ResizeProductImageChunkTopic::FORCE => true,
                    ResizeProductImageChunkTopic::DIMENSIONS => ['original', 'large'],
                    ResizeProductImageChunkTopic::IMAGE_IDS => [1, 2, 3, 4, 5],
                ],
                'expectedBody' => [
                    ResizeProductImageChunkTopic::JOB_ID => 456,
                    ResizeProductImageChunkTopic::FORCE => true,
                    ResizeProductImageChunkTopic::DIMENSIONS => ['original', 'large'],
                    ResizeProductImageChunkTopic::IMAGE_IDS => [1, 2, 3, 4, 5],
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
                'exceptionMessage' => '/The required option "jobId" is missing./',
            ],
            'invalid "jobId" type' => [
                'body' => [
                    ResizeProductImageChunkTopic::JOB_ID => 'invalid',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "jobId" with value "invalid" ' .
                    'is expected to be of type "int", but is of type "string"./',
            ],
            'invalid "force" type' => [
                'body' => [
                    ResizeProductImageChunkTopic::JOB_ID => 123,
                    ResizeProductImageChunkTopic::FORCE => 'invalid',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "force" with value "invalid" ' .
                    'is expected to be of type "bool", but is of type "string"./',
            ],
            'invalid "dimensions" type' => [
                'body' => [
                    ResizeProductImageChunkTopic::JOB_ID => 123,
                    ResizeProductImageChunkTopic::DIMENSIONS => 'invalid',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "dimensions" with value "invalid" ' .
                    'is expected to be of type "array" or "null", but is of type "string"./',
            ],
            'invalid "imageIds" type' => [
                'body' => [
                    ResizeProductImageChunkTopic::JOB_ID => 123,
                    ResizeProductImageChunkTopic::IMAGE_IDS => 'invalid',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "imageIds" with value "invalid" ' .
                    'is expected to be of type "array" or "null", but is of type "string"./',
            ],
        ];
    }

    public function testGetName(): void
    {
        self::assertSame('oro_product.image_resize_chunk', ResizeProductImageChunkTopic::getName());
    }
}
