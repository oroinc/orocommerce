<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateCacheTopic;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateContentNodeCacheTopic;
use Oro\Bundle\WebCatalogBundle\Async\WebCatalogCacheProcessor;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\WebCatalogRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

class WebCatalogCacheProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;
    use LoggerAwareTraitTestTrait;

    private JobRunner|\PHPUnit\Framework\MockObject\MockObject $jobRunner;

    private MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject $producer;

    private ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager;

    private WebCatalogCacheProcessor $processor;

    protected function setUp(): void
    {
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->processor = new WebCatalogCacheProcessor(
            $this->jobRunner,
            $this->producer,
            $registry,
            $this->configManager,
            $this->createMock(LoggerInterface::class)
        );
        $this->setUpLoggerMock($this->processor);

        $this->webCatalogRepository = $this->createMock(WebCatalogRepository::class);
        $this->websiteRepository = $this->createMock(WebsiteRepository::class);
        $this->contentNodeRepo = $this->createMock(ContentNodeRepository::class);
        $registry
            ->expects(self::any())
            ->method('getRepository')
            ->willReturnMap([
                [WebCatalog::class, null, $this->webCatalogRepository],
                [Website::class, null, $this->websiteRepository],
                [ContentNode::class, null, $this->contentNodeRepo],
            ]);
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals(
            [WebCatalogCalculateCacheTopic::getName()],
            WebCatalogCacheProcessor::getSubscribedTopics()
        );
    }

    public function testProcess(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $message = $this->createMessage('mid-42', [WebCatalogCalculateCacheTopic::WEB_CATALOG_ID => 1]);

        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => 1]);
        $website1 = $this->getEntity(Website::class, ['id' => 1]);
        $website2 = $this->getEntity(Website::class, ['id' => 2]);
        $websites = [1 => $website1, 2 => $website2];
        $node = $this->getEntity(ContentNode::class, ['id' => 3]);
        $this->webCatalogRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['id' => '1'])
            ->willReturn($webCatalog);
        $this->websiteRepository->expects(self::once())
            ->method('getAllWebsites')
            ->willReturn($websites);
        $this->contentNodeRepo->expects(self::once())
            ->method('findOneBy')
            ->with(['id' => 3])
            ->willReturn($node);
        $this->contentNodeRepo->expects(self::once())
            ->method('findBy')
            ->with(['id' => [3]])
            ->willReturn([$node]);

        $this->configManager
            ->expects(self::once())
            ->method('getValues')
            ->with('oro_web_catalog.web_catalog', $websites)
            ->willReturn([1 => 1, 2 => 3]);

        $this->configManager
            ->expects(self::once())
            ->method('get')
            ->with('oro_web_catalog.navigation_root', false, false, $website1)
            ->willReturn(3);

        $this->assertProcessCalled($node);
        self::assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $session));
    }

    public function testProcessWithProperScopeResolvedWebsiteForCeApp(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $message = $this->createMessage('mid-42', [WebCatalogCalculateCacheTopic::WEB_CATALOG_ID => 1]);

        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => 1]);
        $websites = [$this->getEntity(Website::class, ['id' => 1])];
        $node = $this->getEntity(ContentNode::class, ['id' => 3]);
        $this->webCatalogRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['id' => '1'])
            ->willReturn($webCatalog);
        $this->websiteRepository->expects(self::once())
            ->method('getAllWebsites')
            ->willReturn($websites);
        $this->contentNodeRepo->expects(self::once())
            ->method('findOneBy')
            ->with(['id' => 3])
            ->willReturn($node);
        $this->contentNodeRepo->expects(self::once())
            ->method('findBy')
            ->with(['id' => [3]])
            ->willReturn([$node]);

        $this->configManager
            ->expects(self::once())
            ->method('getValues')
            ->with('oro_web_catalog.web_catalog', $websites)
            ->willReturn([0 => 1]);

        // Bellow is the main aim of the test, its propose is to have scope identifier
        // of only website resolved to NULL value for CE application.
        $this->configManager
            ->expects(self::once())
            ->method('get')
            ->with('oro_web_catalog.navigation_root', false, false, null)
            ->willReturn(3);

        $this->assertProcessCalled($node);
        self::assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $session));
    }

    private function assertProcessCalled(ContentNode $node): void
    {
        $this->producer->expects(self::once())
            ->method('send')
            ->with(
                WebCatalogCalculateContentNodeCacheTopic::getName(),
                [WebCatalogCalculateContentNodeCacheTopic::CONTENT_NODE_ID => $node->getId()]
            );
        $this->assertUniqueJobExecuted();
    }

    private function createMessage(string $messageId, array $body): Message
    {
        $message = new Message();
        $message->setMessageId($messageId);
        $message->setBody($body);

        return $message;
    }

    private function assertUniqueJobExecuted(): void
    {
        $job = $this->createMock(Job::class);
        $this->jobRunner->expects(self::once())
            ->method('runUnique')
            ->willReturnCallback(
                function ($ownerId, $name, $closure) use ($job) {
                    $this->assertEquals('mid-42', $ownerId);
                    $this->assertEquals(WebCatalogCalculateCacheTopic::getName() . ':1', $name);

                    return $closure($this->jobRunner, $job);
                }
            );
    }
}
