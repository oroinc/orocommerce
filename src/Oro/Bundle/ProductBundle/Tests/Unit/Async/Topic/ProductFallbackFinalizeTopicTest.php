<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\ProductBundle\Async\Topic\ProductFallbackFinalizeTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;

final class ProductFallbackFinalizeTopicTest extends AbstractTopicTestCase
{
    #[\Override]
    protected function getTopic(): TopicInterface
    {
        return new ProductFallbackFinalizeTopic();
    }

    #[\Override]
    public function validBodyDataProvider(): array
    {
        return [
            'empty body' => [
                'body' => [],
                'expectedBody' => [],
            ],
            'with job_id' => [
                'body' => [
                    ProductFallbackFinalizeTopic::JOB_ID => 123,
                ],
                'expectedBody' => [
                    ProductFallbackFinalizeTopic::JOB_ID => 123,
                ],
            ],
        ];
    }

    #[\Override]
    public function invalidBodyDataProvider(): array
    {
        return [];
    }

    public function testGetName(): void
    {
        self::assertSame('oro.platform.post_upgrade.finalize', ProductFallbackFinalizeTopic::getName());
    }

    public function testGetDescription(): void
    {
        self::assertSame(
            'Finalizes product fallback updates by resolving notification alerts when processing is complete.',
            $this->getTopic()->getDescription()
        );
    }
}
