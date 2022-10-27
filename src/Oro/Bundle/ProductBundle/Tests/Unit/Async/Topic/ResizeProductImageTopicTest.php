<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\ProductBundle\Async\Topic\ResizeProductImageTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ResizeProductImageTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new ResizeProductImageTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'required options' => [
                'body' => [
                    ResizeProductImageTopic::PRODUCT_IMAGE_ID_OPTION => 1,
                ],
                'expectedBody' => [
                    ResizeProductImageTopic::PRODUCT_IMAGE_ID_OPTION => 1,
                    ResizeProductImageTopic::FORCE_OPTION => false,
                    ResizeProductImageTopic::DIMENSIONS_OPTION => [],
                ],
            ],
            'all options' => [
                'body' => [
                    ResizeProductImageTopic::PRODUCT_IMAGE_ID_OPTION => 1,
                    ResizeProductImageTopic::FORCE_OPTION => true,
                    ResizeProductImageTopic::DIMENSIONS_OPTION => ['original'],
                ],
                'expectedBody' => [
                    ResizeProductImageTopic::PRODUCT_IMAGE_ID_OPTION => 1,
                    ResizeProductImageTopic::FORCE_OPTION => true,
                    ResizeProductImageTopic::DIMENSIONS_OPTION => ['original'],
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
                'exceptionMessage' => '/The required option "productImageId" is missing./',
            ],
            'invalid "productImageId" type' => [
                'body' => [
                    ResizeProductImageTopic::PRODUCT_IMAGE_ID_OPTION => 'test'
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "productImageId" with value "test" ' .
                    'is expected to be of type "int", but is of type "string"./',
            ],
            'invalid "force" type' => [
                'body' => [
                    ResizeProductImageTopic::PRODUCT_IMAGE_ID_OPTION => 1,
                    ResizeProductImageTopic::FORCE_OPTION => 'test',
                    ResizeProductImageTopic::DIMENSIONS_OPTION => null,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "force" with value "test" ' .
                    'is expected to be of type "bool", but is of type "string"./',
            ],
            'invalid "dimensions" type' => [
                'body' => [
                    ResizeProductImageTopic::PRODUCT_IMAGE_ID_OPTION => 1,
                    ResizeProductImageTopic::FORCE_OPTION => true,
                    ResizeProductImageTopic::DIMENSIONS_OPTION => 'test',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "dimensions" with value "test" ' .
                    'is expected to be of type "array" or "null", but is of type "string"./',
            ],
        ];
    }
}
