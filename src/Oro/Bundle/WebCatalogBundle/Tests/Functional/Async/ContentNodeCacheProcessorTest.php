<?php

declare(strict_types=1);

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\Async;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateContentNodeCacheTopic;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateContentNodeTreeCacheTopic;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentNodesData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogScopes;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;

/**
 * @dbIsolationPerTest
 */
class ContentNodeCacheProcessorTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testProcessWhenNoContentNode(): void
    {
        $sentMessage = self::sendMessage(
            WebCatalogCalculateContentNodeCacheTopic::getName(),
            [WebCatalogCalculateContentNodeCacheTopic::CONTENT_NODE_ID => PHP_INT_MAX]
        );

        self::consumeMessage($sentMessage);

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor('oro_web_catalog.async.content_node_cache_processor', $sentMessage);
    }

    public function testProcessWhenNoScopes(): void
    {
        $this->loadFixtures([LoadContentNodesData::class]);

        /** @var ContentNode $contentNode */
        $contentNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT);
        $sentMessage = self::sendMessage(
            WebCatalogCalculateContentNodeCacheTopic::getName(),
            [WebCatalogCalculateContentNodeCacheTopic::CONTENT_NODE_ID => $contentNode->getId()]
        );

        self::consumeMessage($sentMessage);

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor('oro_web_catalog.async.content_node_cache_processor', $sentMessage);

        self::assertMessagesEmpty(WebCatalogCalculateContentNodeTreeCacheTopic::getName());
    }

    public function testProcessWithScopes(): void
    {
        $this->loadFixtures([LoadContentNodesData::class, LoadWebCatalogScopes::class]);

        /** @var ContentNode $contentNode */
        $contentNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT);
        /** @var Scope $scope */
        $scope = $this->getReference(LoadWebCatalogScopes::SCOPE1);
        $sentMessage = self::sendMessage(
            WebCatalogCalculateContentNodeCacheTopic::getName(),
            [WebCatalogCalculateContentNodeCacheTopic::CONTENT_NODE_ID => $contentNode->getId()]
        );

        self::consumeMessage($sentMessage);

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor('oro_web_catalog.async.content_node_cache_processor', $sentMessage);

        $sentChildMessage = self::getSentMessage(WebCatalogCalculateContentNodeTreeCacheTopic::getName(), false);
        self::assertEquals(
            $contentNode->getId(),
            $sentChildMessage->getBody()[WebCatalogCalculateContentNodeTreeCacheTopic::CONTENT_NODE]
        );
        self::assertEquals(
            $scope->getId(),
            $sentChildMessage->getBody()[WebCatalogCalculateContentNodeTreeCacheTopic::SCOPE]
        );

        self::consumeMessage($sentChildMessage);

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentChildMessage);
        self::assertProcessedMessageProcessor(
            'oro_web_catalog.async.content_node_tree_cache_processor',
            $sentChildMessage
        );
    }
}
