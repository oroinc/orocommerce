<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Async\ContentNodeCacheProcessor;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateContentNodeCacheTopic;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateContentNodeTreeCacheTopic;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\WebCatalogRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class ContentNodeCacheProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private JobRunner|\PHPUnit\Framework\MockObject\MockObject $jobRunner;

    private MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject $producer;

    private ContentNodeRepository|\PHPUnit\Framework\MockObject\MockObject $contentNodeRepository;

    private WebCatalogRepository|\PHPUnit\Framework\MockObject\MockObject $webCatalogRepository;

    private ContentNodeCacheProcessor $processor;

    protected function setUp(): void
    {
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->contentNodeRepository = $this->createMock(ContentNodeRepository::class);
        $this->webCatalogRepository = $this->createMock(WebCatalogRepository::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->willReturnMap([
                [ContentNode::class, null, $this->contentNodeRepository],
                [WebCatalog::class, null, $this->webCatalogRepository],
            ]);

        $this->processor = new ContentNodeCacheProcessor(
            $this->jobRunner,
            $this->producer,
            $doctrine
        );
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals(
            [WebCatalogCalculateContentNodeCacheTopic::getName()],
            ContentNodeCacheProcessor::getSubscribedTopics()
        );
    }

    public function testProcess(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $message = $this->createMessage('mid-42', [WebCatalogCalculateContentNodeCacheTopic::CONTENT_NODE_ID => 3]);

        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => 1]);
        $node = $this->getEntity(ContentNode::class, ['id' => 3, 'webCatalog' => $webCatalog]);

        $this->contentNodeRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['id' => 3])
            ->willReturn($node);

        $this->assertProcessCalled($message, $webCatalog, $node);
        self::assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $session));
    }

    private function assertProcessCalled($message, WebCatalog $webCatalog, ContentNode $node): void
    {
        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => 21]);
        $scopes = [$scope];
        $this->webCatalogRepository->expects(self::once())
            ->method('getUsedScopes')
            ->with($webCatalog)
            ->willReturn($scopes);

        $this->producer->expects(self::once())
            ->method('send')
            ->with(
                WebCatalogCalculateContentNodeTreeCacheTopic::getName(),
                [
                    WebCatalogCalculateContentNodeTreeCacheTopic::CONTENT_NODE => $node->getId(),
                    WebCatalogCalculateContentNodeTreeCacheTopic::SCOPE => $scope->getId(),
                    WebCatalogCalculateContentNodeTreeCacheTopic::JOB_ID => 123,
                ]
            );
        $this->assertUniqueJobExecuted($message);
        $this->assertChildJobCreated($scope, $node);
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

    private function assertChildJobCreated(Scope $scope, ContentNode $node): void
    {
        $childJob = $this->createMock(Job::class);
        $childJob->expects(self::once())
            ->method('getId')
            ->willReturn(123);
        $this->jobRunner->expects(self::once())
            ->method('createDelayed')
            ->willReturnCallback(
                function ($name, $closure) use ($childJob, $scope, $node) {
                    $this->assertEquals(
                        sprintf(
                            '%s:%s:%s',
                            WebCatalogCalculateContentNodeTreeCacheTopic::getName(),
                            $scope->getId(),
                            $node->getId()
                        ),
                        $name
                    );

                    return $closure($this->jobRunner, $childJob);
                }
            );
    }
}
