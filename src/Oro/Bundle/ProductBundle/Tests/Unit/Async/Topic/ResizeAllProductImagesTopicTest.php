<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\ProductBundle\Async\Topic\ResizeAllProductImagesTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class ResizeAllProductImagesTopicTest extends AbstractTopicTestCase
{
    #[\Override]
    protected function getTopic(): TopicInterface
    {
        return new ResizeAllProductImagesTopic();
    }

    #[\Override]
    public function validBodyDataProvider(): array
    {
        return [
            'default options' => [
                'body' => [],
                'expectedBody' => [
                    ResizeAllProductImagesTopic::FORCE => false,
                    ResizeAllProductImagesTopic::DIMENSIONS => null,
                ],
            ],
            'force only' => [
                'body' => [
                    ResizeAllProductImagesTopic::FORCE => true,
                ],
                'expectedBody' => [
                    ResizeAllProductImagesTopic::FORCE => true,
                    ResizeAllProductImagesTopic::DIMENSIONS => null,
                ],
            ],
            'all options' => [
                'body' => [
                    ResizeAllProductImagesTopic::FORCE => true,
                    ResizeAllProductImagesTopic::DIMENSIONS => ['original', 'large'],
                ],
                'expectedBody' => [
                    ResizeAllProductImagesTopic::FORCE => true,
                    ResizeAllProductImagesTopic::DIMENSIONS => ['original', 'large'],
                ],
            ],
        ];
    }

    #[\Override]
    public function invalidBodyDataProvider(): array
    {
        return [
            'invalid "force" type' => [
                'body' => [
                    ResizeAllProductImagesTopic::FORCE => 'invalid',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "force" with value "invalid" ' .
                    'is expected to be of type "bool", but is of type "string"./',
            ],
            'invalid "dimensions" type' => [
                'body' => [
                    ResizeAllProductImagesTopic::DIMENSIONS => 'invalid',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "dimensions" with value "invalid" ' .
                    'is expected to be of type "array" or "null", but is of type "string"./',
            ],
        ];
    }

    public function testGetName(): void
    {
        self::assertSame('oro_product.image_resize_all', ResizeAllProductImagesTopic::getName());
    }

    public function testCreateJobName(): void
    {
        $topic = new ResizeAllProductImagesTopic();

        self::assertSame('oro_product.image_resize_all', $topic->createJobName([]));
    }
}
