<?php

declare(strict_types=1);

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\Async;

use Oro\Bundle\MessageQueueBundle\Test\Functional\JobsAwareTestTrait;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateContentNodeTreeCacheTopic;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentNodesData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogScopes;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;

/**
 * @dbIsolationPerTest
 */
class ContentNodeTreeCacheProcessorTest extends WebTestCase
{
    use MessageQueueExtension;
    use JobsAwareTestTrait;

    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testProcessWhenNoContentNodeNoScope(): void
    {
        $childJob = $this->createDelayedJob();
        $sentMessage = self::sendMessage(
            WebCatalogCalculateContentNodeTreeCacheTopic::getName(),
            [
                WebCatalogCalculateContentNodeTreeCacheTopic::JOB_ID => $childJob->getId(),
                WebCatalogCalculateContentNodeTreeCacheTopic::CONTENT_NODE => PHP_INT_MAX,
                WebCatalogCalculateContentNodeTreeCacheTopic::SCOPE => PHP_INT_MAX,
            ]
        );

        self::consumeMessage($sentMessage);

        self::assertProcessedMessageStatus(MessageProcessorInterface::REJECT, $sentMessage);
        self::assertProcessedMessageProcessor('oro_web_catalog.async.content_node_tree_cache_processor', $sentMessage);

        self::assertEquals(Job::STATUS_FAILED, $this->getJobProcessor()->findJobById($childJob->getId())?->getStatus());
    }

    public function testProcess(): void
    {
        $this->loadFixtures(
            [
                LoadContentNodesData::class,
                LoadWebCatalogScopes::class,
            ]
        );

        /** @var ContentNode $contentNode */
        $contentNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT);
        /** @var Scope $scope */
        $scope = $this->getReference(LoadWebCatalogScopes::SCOPE1);

        $contentNodeTreeCache = self::getContainer()->get('oro_web_catalog.content_node_tree_cache.root');
        self::assertEmpty($contentNodeTreeCache->fetch($contentNode->getId(), [$scope->getId()]));

        $childJob = $this->createDelayedJob();
        $sentMessage = self::sendMessage(
            WebCatalogCalculateContentNodeTreeCacheTopic::getName(),
            [
                WebCatalogCalculateContentNodeTreeCacheTopic::JOB_ID => $childJob->getId(),
                WebCatalogCalculateContentNodeTreeCacheTopic::CONTENT_NODE => $contentNode->getId(),
                WebCatalogCalculateContentNodeTreeCacheTopic::SCOPE => $scope->getId(),
            ]
        );

        self::consumeMessage($sentMessage);

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor('oro_web_catalog.async.content_node_tree_cache_processor', $sentMessage);

        self::assertEquals(
            Job::STATUS_SUCCESS,
            $this->getJobProcessor()->findJobById($childJob->getId())?->getStatus()
        );

        $contentNodeTreeCache = self::getContainer()->get('oro_web_catalog.content_node_tree_cache.root');
        self::assertNotEmpty($contentNodeTreeCache->fetch($contentNode->getId(), [$scope->getId()]));
    }
}
