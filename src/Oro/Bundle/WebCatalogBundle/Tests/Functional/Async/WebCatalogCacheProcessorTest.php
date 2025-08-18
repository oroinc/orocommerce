<?php

declare(strict_types=1);

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\Async;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateCacheTopic;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateContentNodeCacheTopic;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogUsageProvider;
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
    use ConfigManagerAwareTestTrait;
    use MessageQueueExtension;
    use DefaultWebsiteIdTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testProcessWhenNoContentNodeNoScope(): void
    {
        $sentMessage = self::sendMessage(
            WebCatalogCalculateCacheTopic::getName(),
            [WebCatalogCalculateCacheTopic::WEB_CATALOG_ID => self::BIGINT]
        );

        self::consume(1);

        self::assertProcessedMessageStatus(MessageProcessorInterface::REJECT, $sentMessage);
        self::assertProcessedMessageProcessor('oro_web_catalog.async.web_catalog_cache_processor', $sentMessage);

        self::assertTrue(
            self::getLoggerTestHandler()->hasError('Root node for the web catalog #' . self::BIGINT . ' is not found')
        );
    }

    public function testProcess(): void
    {
        $this->loadFixtures([
            LoadContentNodesData::class,
            LoadWebCatalogData::class
        ]);

        /** @var WebCatalogInterface $webCatalog */
        $webCatalog = $this->getReference(LoadWebCatalogData::CATALOG_1);

        $configManager = self::getConfigManager();
        $initialWebCatalogId = $configManager->get(WebCatalogUsageProvider::SETTINGS_KEY);
        $configManager->set(WebCatalogUsageProvider::SETTINGS_KEY, $webCatalog->getId());
        $configManager->flush();
        try {
            $sentMessage = self::sendMessage(
                WebCatalogCalculateCacheTopic::getName(),
                [WebCatalogCalculateCacheTopic::WEB_CATALOG_ID => $webCatalog->getId()]
            );
        } finally {
            $configManager->set(WebCatalogUsageProvider::SETTINGS_KEY, $initialWebCatalogId);
            $configManager->flush();
        }

        self::consumeMessage($sentMessage);
        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor(
            'oro_web_catalog.async.web_catalog_cache_processor',
            $sentMessage
        );

        $sentChildMessage = self::getSentMessage(WebCatalogCalculateContentNodeCacheTopic::getName(), false);
        self::consumeMessage($sentChildMessage);
        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentChildMessage);
        self::assertProcessedMessageProcessor(
            'oro_web_catalog.async.content_node_cache_processor',
            $sentChildMessage
        );
    }
}
