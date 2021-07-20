<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Async\ContentNodeCacheProcessor;
use Oro\Bundle\WebCatalogBundle\Async\Topics;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\WebCatalogRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class ContentNodeCacheProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var JobRunner|\PHPUnit\Framework\MockObject\MockObject */
    private $jobRunner;

    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $producer;

    /** @var ContentNodeRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $contentNodeRepository;

    /** @var WebCatalogRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $webCatalogRepository;

    /** @var ContentNodeCacheProcessor */
    private $processor;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->contentNodeRepository = $this->createMock(ContentNodeRepository::class);
        $this->webCatalogRepository = $this->createMock(WebCatalogRepository::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [ContentNode::class, null, $this->contentNodeRepository],
                [WebCatalog::class, null, $this->webCatalogRepository]
            ]);

        $this->processor = new ContentNodeCacheProcessor(
            $this->jobRunner,
            $this->producer,
            $doctrine
        );
    }

    public function testGetSubscribedTopics()
    {
        $this->assertEquals([Topics::CALCULATE_CONTENT_NODE_CACHE], $this->processor->getSubscribedTopics());
    }

    public function testProcess()
    {
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $message = $this->createMessage('mid-42', '{"contentNodeId":3}');

        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => 1]);
        $node = $this->getEntity(ContentNode::class, ['id' => 3, 'webCatalog' => $webCatalog]);

        $this->contentNodeRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => 3])
            ->willReturn($node);

        $this->assertProcessCalled($webCatalog, $node);
        $this->assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $session));
    }

    /**
     * @return array
     */
    public function webCatalogDataProvider()
    {
        return [
            'scalar' => [
                'messageBody' => '1'
            ],
            'array' => [
                'messageBody' => '{"webCatalogId": 1}'
            ]
        ];
    }

    private function assertProcessCalled(WebCatalog $webCatalog, ContentNode $node)
    {
        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => 21]);
        $scopes = [$scope];
        $this->webCatalogRepository->expects($this->once())
            ->method('getUsedScopes')
            ->with($webCatalog)
            ->willReturn($scopes);

        $this->producer->expects($this->once())
            ->method('send')
            ->with(
                Topics::CALCULATE_CONTENT_NODE_TREE_BY_SCOPE,
                [
                    'contentNode' => $node->getId(),
                    'scope' => $scope->getId(),
                    'jobId' => 123
                ]
            );
        $this->assertUniqueJobExecuted();
        $this->assertChildJobCreated($scope, $node);
    }

    /**
     * @param string $messageId
     * @param string $body
     *
     * @return Message
     */
    private function createMessage($messageId, $body)
    {
        $message = new Message();
        $message->setMessageId($messageId);
        $message->setBody($body);

        return $message;
    }

    private function assertUniqueJobExecuted()
    {
        /** @var Job|\PHPUnit\Framework\MockObject\MockObject $job */
        $job = $this->createMock(Job::class);
        $this->jobRunner->expects($this->once())
            ->method('runUnique')
            ->willReturnCallback(
                function ($ownerId, $name, $closure) use ($job) {
                    $this->assertEquals('mid-42', $ownerId);
                    $this->assertEquals(Topics::CALCULATE_CONTENT_NODE_CACHE . ':3', $name);

                    return $closure($this->jobRunner, $job);
                }
            );
    }

    private function assertChildJobCreated(Scope $scope, ContentNode $node)
    {
        /** @var Job|\PHPUnit\Framework\MockObject\MockObject $job */
        $childJob = $this->createMock(Job::class);
        $childJob->expects($this->once())
            ->method('getId')
            ->willReturn(123);
        $this->jobRunner->expects($this->once())
            ->method('createDelayed')
            ->willReturnCallback(
                function ($name, $closure) use ($childJob, $scope, $node) {
                    $this->assertEquals(
                        sprintf(
                            '%s:%s:%s',
                            Topics::CALCULATE_CONTENT_NODE_TREE_BY_SCOPE,
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
