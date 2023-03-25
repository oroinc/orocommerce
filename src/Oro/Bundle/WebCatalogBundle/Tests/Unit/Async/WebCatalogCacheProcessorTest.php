<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Async;

use Doctrine\Persistence\ManagerRegistry;
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
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class WebCatalogCacheProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;
    use LoggerAwareTraitTestTrait;

    private JobRunner|\PHPUnit\Framework\MockObject\MockObject $jobRunner;

    private MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject $producer;

    private WebCatalogCacheProcessor $processor;

    protected function setUp(): void
    {
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);

        $this->processor = new WebCatalogCacheProcessor(
            $this->jobRunner,
            $this->producer,
            $registry
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
        $webCatalogId = 1;
        $message = $this->createMessage('mid-42', [WebCatalogCalculateCacheTopic::WEB_CATALOG_ID => $webCatalogId]);

        $node = $this->getEntity(ContentNode::class, ['id' => 3]);
        $this->contentNodeRepo->expects(self::once())
            ->method('getRootNodeIdByWebCatalog')
            ->with($webCatalogId)
            ->willReturn($node->getId());

        $this->producer->expects(self::once())
            ->method('send')
            ->with(
                WebCatalogCalculateContentNodeCacheTopic::getName(),
                [WebCatalogCalculateContentNodeCacheTopic::CONTENT_NODE_ID => $node->getId()]
            );
        $this->assertUniqueJobExecuted($message);

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testProcessWhenNoRootNodeId(): void
    {
        $webCatalogId = 1;
        $message = $this->createMessage('mid-42', [WebCatalogCalculateCacheTopic::WEB_CATALOG_ID => $webCatalogId]);

        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => 1]);
        $this->contentNodeRepo->expects(self::once())
            ->method('getRootNodeIdByWebCatalog')
            ->with($webCatalog->getId())
            ->willReturn(null);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with('Root node for the web catalog #{webCatalogId} is not found', $message->getBody());

        $this->assertUniqueJobExecuted($message);

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    private function createMessage(string $messageId, array $body): Message
    {
        $message = new Message();
        $message->setMessageId($messageId);
        $message->setBody($body);

        return $message;
    }

    private function assertUniqueJobExecuted(MessageInterface $expectedMessage): void
    {
        $job = $this->createMock(Job::class);
        $this->jobRunner->expects(self::once())
            ->method('runUniqueByMessage')
            ->willReturnCallback(
                function ($actualMessage, $closure) use ($job, $expectedMessage) {
                    $this->assertSame($actualMessage, $expectedMessage);

                    return $closure($this->jobRunner, $job);
                }
            );
    }
}
