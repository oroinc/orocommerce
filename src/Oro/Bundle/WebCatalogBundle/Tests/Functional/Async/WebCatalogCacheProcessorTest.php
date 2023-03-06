<?php

declare(strict_types=1);

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\Async;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateCacheTopic;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateContentNodeCacheTopic;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadConfigValue;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentNodesData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits\DefaultWebsiteIdTestTrait;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\WebCatalog\Entity\WebCatalogInterface;

/**
 * @dbIsolationPerTest
 */
class WebCatalogCacheProcessorTest extends WebTestCase
{
    use MessageQueueExtension;
    use ConfigManagerAwareTestTrait;
    use DefaultWebsiteIdTestTrait;

    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testProcessWhenNoContentNodeNoScope(): void
    {
        $sentMessage = self::sendMessage(
            WebCatalogCalculateCacheTopic::getName(),
            [WebCatalogCalculateCacheTopic::WEB_CATALOG_ID => PHP_INT_MAX]
        );

        self::consume(1);

        self::assertProcessedMessageStatus(MessageProcessorInterface::REJECT, $sentMessage);
        self::assertProcessedMessageProcessor('oro_web_catalog.async.web_catalog_cache_processor', $sentMessage);

        self::assertTrue(
            self::getLoggerTestHandler()->hasError('Root node for the web catalog #' . PHP_INT_MAX . ' is not found')
        );
    }

    public function testProcess(): void
    {
        $this->loadFixtures(
            [
                LoadContentNodesData::class,
                LoadWebCatalogData::class,
                LoadConfigValue::class,
            ]
        );
        /** @var WebCatalogInterface $webCatalog */
        $webCatalog = $this->getReference(LoadWebCatalogData::CATALOG_1);

        $sentMessage = self::sendMessage(
            WebCatalogCalculateCacheTopic::getName(),
            [WebCatalogCalculateCacheTopic::WEB_CATALOG_ID => $webCatalog->getId()]
        );

        self::consumeMessage($sentMessage);

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor('oro_web_catalog.async.web_catalog_cache_processor', $sentMessage);

        $sentChildMessage = self::getSentMessage(WebCatalogCalculateContentNodeCacheTopic::getName(), false);

        self::consumeMessage($sentChildMessage);

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentChildMessage);
        self::assertProcessedMessageProcessor('oro_web_catalog.async.content_node_cache_processor', $sentChildMessage);
    }
}
