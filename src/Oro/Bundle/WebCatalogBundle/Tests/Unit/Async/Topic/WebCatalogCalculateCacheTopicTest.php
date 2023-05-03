<?php

declare(strict_types=1);

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateCacheTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class WebCatalogCalculateCacheTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new WebCatalogCalculateCacheTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'required only' => [
                'body' => [WebCatalogCalculateCacheTopic::WEB_CATALOG_ID => 42],
                'expectedBody' => [WebCatalogCalculateCacheTopic::WEB_CATALOG_ID => 42],
            ],
            'required with alternative types' => [
                'body' => [WebCatalogCalculateCacheTopic::WEB_CATALOG_ID => '42'],
                'expectedBody' => [WebCatalogCalculateCacheTopic::WEB_CATALOG_ID => 42],
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required option "webCatalogId" is missing./',
            ],
            'webCatalogId has invalid type' => [
                'body' => [WebCatalogCalculateCacheTopic::WEB_CATALOG_ID => new \stdClass()],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "webCatalogId" with value stdClass is expected '
                    . 'to be of type "int" or "string"/',
            ],
        ];
    }

    public function testCreateJobName(): void
    {
        self::assertSame(
            'oro.web_catalog.calculate_cache:42',
            $this->getTopic()->createJobName([
                WebCatalogCalculateCacheTopic::WEB_CATALOG_ID => 42
            ])
        );
    }
}
