<?php

declare(strict_types=1);

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateContentNodeCacheTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class WebCatalogCalculateContentNodeCacheTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new WebCatalogCalculateContentNodeCacheTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'required only' => [
                'body' => [WebCatalogCalculateContentNodeCacheTopic::CONTENT_NODE_ID => 42],
                'expectedBody' => [WebCatalogCalculateContentNodeCacheTopic::CONTENT_NODE_ID => 42],
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required option "contentNodeId" is missing./',
            ],
            'contentNodeId has invalid type' => [
                'body' => [WebCatalogCalculateContentNodeCacheTopic::CONTENT_NODE_ID => new \stdClass()],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "contentNodeId" with value stdClass is expected '
                    .'to be of type "int"/',
            ],
        ];
    }

    public function testCreateJobName(): void
    {
        self::assertSame(
            'oro.web_catalog.calculate_cache.content_node:42',
            $this->getTopic()->createJobName([
                WebCatalogCalculateContentNodeCacheTopic::CONTENT_NODE_ID => 42
            ])
        );
    }
}
