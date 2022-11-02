<?php

declare(strict_types=1);

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\Async;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateCacheTopic;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogResolveContentNodeSlugsTopic;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Generator\SlugGenerator;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentNodesData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogScopes;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;

/**
 * @dbIsolationPerTest
 */
class ContentNodeSlugsProcessorTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testProcessWhenNoContentNode(): void
    {
        $sentMessage = self::sendMessage(
            WebCatalogResolveContentNodeSlugsTopic::getName(),
            [
                WebCatalogResolveContentNodeSlugsTopic::ID => PHP_INT_MAX,
                WebCatalogResolveContentNodeSlugsTopic::CREATE_REDIRECT => false,
            ]
        );

        self::consumeMessage($sentMessage);

        self::assertProcessedMessageStatus(MessageProcessorInterface::REJECT, $sentMessage);
        self::assertProcessedMessageProcessor('oro_web_catalog.async.content_node_slug_processor', $sentMessage);

        self::assertMessagesEmpty(WebCatalogCalculateCacheTopic::getName());
    }

    public function testProcessWhenRootContentNode(): void
    {
        $this->loadFixtures([LoadContentNodesData::class]);

        /** @var ContentNode $contentNode */
        $contentNode = $this->getReference(LoadContentNodesData::CATALOG_2_ROOT);

        self::assertCount(1, $contentNode->getLocalizedUrls());
        self::assertEquals(
            '/' . LoadContentNodesData::CATALOG_2_ROOT,
            $contentNode->getLocalizedUrls()->first()->getText()
        );

        $sentMessage = self::sendMessage(
            WebCatalogResolveContentNodeSlugsTopic::getName(),
            [
                WebCatalogResolveContentNodeSlugsTopic::ID => $contentNode->getId(),
                WebCatalogResolveContentNodeSlugsTopic::CREATE_REDIRECT => false,
            ]
        );

        self::consumeMessage($sentMessage);

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor('oro_web_catalog.async.content_node_slug_processor', $sentMessage);

        /** @var ContentNode $contentNode */
        $contentNode = $this->getReference(LoadContentNodesData::CATALOG_2_ROOT);
        self::assertCount(1, $contentNode->getLocalizedUrls());
        self::assertEquals(SlugGenerator::ROOT_URL, $contentNode->getLocalizedUrls()->first()->getText());

        $childMessage = self::getSentMessage(WebCatalogCalculateCacheTopic::getName());
        self::assertEquals(
            $contentNode->getWebCatalog()->getId(),
            $childMessage[WebCatalogCalculateCacheTopic::WEB_CATALOG_ID]
        );
    }

    public function testProcessWithChildContentNodes(): void
    {
        $this->loadFixtures([LoadContentNodesData::class, LoadWebCatalogScopes::class]);

        /** @var ContentNode $contentNode */
        $contentNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1);
        $this->updateDefaultSlugPrototype($contentNode, LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1 . '-new');

        $sentMessage = self::sendMessage(
            WebCatalogResolveContentNodeSlugsTopic::getName(),
            [
                WebCatalogResolveContentNodeSlugsTopic::ID => $contentNode->getId(),
                WebCatalogResolveContentNodeSlugsTopic::CREATE_REDIRECT => false,
            ]
        );

        self::consumeMessage($sentMessage);

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor('oro_web_catalog.async.content_node_slug_processor', $sentMessage);

        /** @var ContentNode $contentNode */
        $contentNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1);
        self::assertCount(1, $contentNode->getLocalizedUrls());
        self::assertEquals(
            '/' . LoadContentNodesData::CATALOG_1_ROOT
            . '/' . LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1 . '-new',
            $contentNode->getLocalizedUrls()->first()->getText()
        );

        /** @var ContentNode $childContentNode */
        $childContentNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_1);
        self::assertCount(1, $childContentNode->getLocalizedUrls());
        self::assertEquals(
            '/' . LoadContentNodesData::CATALOG_1_ROOT
            . '/' . LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1 . '-new'
            . '/' . LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_1,
            $childContentNode->getLocalizedUrls()->first()->getText()
        );

        $childMessage = self::getSentMessage(WebCatalogCalculateCacheTopic::getName());
        self::assertEquals(
            $contentNode->getWebCatalog()->getId(),
            $childMessage[WebCatalogCalculateCacheTopic::WEB_CATALOG_ID]
        );
    }

    private function updateDefaultSlugPrototype(ContentNode $contentNode, string $string): void
    {
        $slugPrototype = $contentNode->getDefaultSlugPrototype();
        $slugPrototype->setString($string);
        $entityManager = self::getContainer()
            ->get('doctrine')
            ->getManagerForClass(ContentNode::class);
        $entityManager->persist($contentNode);
        $entityManager->flush();
    }
}
