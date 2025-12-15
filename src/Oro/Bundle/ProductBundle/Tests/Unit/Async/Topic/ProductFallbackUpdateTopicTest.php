<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\ProductBundle\Async\Topic\ProductFallbackUpdateTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

final class ProductFallbackUpdateTopicTest extends AbstractTopicTestCase
{
    #[\Override]
    protected function getTopic(): TopicInterface
    {
        return new ProductFallbackUpdateTopic();
    }

    #[\Override]
    public function validBodyDataProvider(): array
    {
        return [
            'with batch_size' => [
                'body' => [
                    ProductFallbackUpdateTopic::BATCH_SIZE_OPTION => 500,
                ],
                'expectedBody' => [
                    ProductFallbackUpdateTopic::BATCH_SIZE_OPTION => 500,
                ],
            ],
            'with small batch_size' => [
                'body' => [
                    ProductFallbackUpdateTopic::BATCH_SIZE_OPTION => 1,
                ],
                'expectedBody' => [
                    ProductFallbackUpdateTopic::BATCH_SIZE_OPTION => 1,
                ],
            ],
            'with large batch_size' => [
                'body' => [
                    ProductFallbackUpdateTopic::BATCH_SIZE_OPTION => 10000,
                ],
                'expectedBody' => [
                    ProductFallbackUpdateTopic::BATCH_SIZE_OPTION => 10000,
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
                'exceptionMessage' => '/The required option "batch_size" is missing./',
            ],
            'invalid batch_size type' => [
                'body' => [
                    ProductFallbackUpdateTopic::BATCH_SIZE_OPTION => 'invalid',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "batch_size" with value "invalid" ' .
                    'is expected to be of type "int", but is of type "string"./',
            ],
            'zero batch_size' => [
                'body' => [
                    ProductFallbackUpdateTopic::BATCH_SIZE_OPTION => 0,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The batch size must be a positive integer./',
            ],
            'negative batch_size' => [
                'body' => [
                    ProductFallbackUpdateTopic::BATCH_SIZE_OPTION => -100,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The batch size must be a positive integer./',
            ],
        ];
    }

    public function testGetName(): void
    {
        self::assertSame('oro.platform.post_upgrade.update', ProductFallbackUpdateTopic::getName());
    }

    public function testGetDescription(): void
    {
        self::assertSame(
            'Schedules product fallback updates by splitting affected products into chunks.',
            $this->getTopic()->getDescription()
        );
    }

    /**
     * @dataProvider createJobNameDataProvider
     */
    public function testCreateJobName(array $messageBody, string $expectedJobName): void
    {
        self::assertSame($expectedJobName, $this->getTopic()->createJobName($messageBody));
    }

    public function createJobNameDataProvider(): array
    {
        return [
            'with batch_size' => [
                'messageBody' => [
                    ProductFallbackUpdateTopic::BATCH_SIZE_OPTION => 500,
                ],
                'expectedJobName' => 'oro:product:fallback:update:500',
            ],
            'with custom batch_size' => [
                'messageBody' => [
                    ProductFallbackUpdateTopic::BATCH_SIZE_OPTION => 1000,
                ],
                'expectedJobName' => 'oro:product:fallback:update:1000',
            ],
            'without batch_size falls back to default' => [
                'messageBody' => [],
                'expectedJobName' => 'oro:product:fallback:update:500',
            ],
        ];
    }
}
