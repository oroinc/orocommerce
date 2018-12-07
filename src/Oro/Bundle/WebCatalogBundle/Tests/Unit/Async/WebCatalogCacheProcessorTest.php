<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Async\Topics;
use Oro\Bundle\WebCatalogBundle\Async\WebCatalogCacheProcessor;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ScopeMatcher;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\WebCatalogRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

class WebCatalogCacheProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var JobRunner|\PHPUnit\Framework\MockObject\MockObject
     */
    private $jobRunner;

    /**
     * @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $producer;

    /**
     * @var ScopeMatcher|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeMatcher;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var WebCatalogCacheProcessor
     */
    private $processor;

    protected function setUp()
    {
        $this->jobRunner = $this->getMockBuilder(JobRunner::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->scopeMatcher = $this->getMockBuilder(ScopeMatcher::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new WebCatalogCacheProcessor(
            $this->jobRunner,
            $this->producer,
            $this->scopeMatcher,
            $this->registry,
            $this->logger
        );
    }

    public function testGetSubscribedTopics()
    {
        $this->assertEquals([Topics::CALCULATE_WEB_CATALOG_CACHE], $this->processor->getSubscribedTopics());
    }

    public function testProcessException()
    {
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $message = $this->createMessage('');

        $e = new \Exception('Test exception');
        $this->jobRunner->expects($this->once())
            ->method('runUnique')
            ->willThrowException($e);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during queue message processing',
                [
                    'topic' => Topics::CALCULATE_WEB_CATALOG_CACHE,
                    'exception' => $e
                ]
            );

        $this->assertEquals(WebCatalogCacheProcessor::REJECT, $this->processor->process($message, $session));
    }

    public function testProcessSingleWebCatalog()
    {
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $message = $this->createMessage('1');

        /** @var WebCatalog $webCatalog */
        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => 1]);
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => 2]);

        /** @var WebCatalogRepository|\PHPUnit\Framework\MockObject\MockObject $webCatalogRepository */
        $webCatalogRepository = $this->getMockBuilder(WebCatalogRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $webCatalogRepository->expects($this->once())
            ->method('findBy')
            ->with(['id' => '1'])
            ->willReturn([$webCatalog]);

        $this->assertProcessCalled($webCatalogRepository, $webCatalog, $node);

        $this->assertEquals(WebCatalogCacheProcessor::ACK, $this->processor->process($message, $session));
    }

    public function testProcessWebCatalogWithRemovedRoot()
    {
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $message = $this->createMessage('1');

        /** @var WebCatalog $webCatalog */
        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => 1]);

        /** @var WebCatalogRepository|\PHPUnit\Framework\MockObject\MockObject $webCatalogRepository */
        $webCatalogRepository = $this->getMockBuilder(WebCatalogRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $webCatalogRepository->expects($this->once())
            ->method('findBy')
            ->with(['id' => '1'])
            ->willReturn([$webCatalog]);

        $this->assertRepositoryCalls($webCatalogRepository, $webCatalog);

        /** @var Job|\PHPUnit\Framework\MockObject\MockObject $job */
        $this->assertUniqueJobExecuted();
        $this->jobRunner->expects($this->never())
            ->method('createDelayed');

        $this->logger->expects($this->never())
            ->method($this->anything());
        $this->producer->expects($this->never())
            ->method('send');

        $this->assertEquals(WebCatalogCacheProcessor::ACK, $this->processor->process($message, $session));
    }

    public function testProcessAllWebCatalogs()
    {
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $message = $this->createMessage('');

        /** @var WebCatalog $webCatalog */
        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => 1]);
        /** @var ContentNode $rootNode */
        $rootNode = $this->getEntity(ContentNode::class, ['id' => 2]);

        /** @var WebCatalogRepository|\PHPUnit\Framework\MockObject\MockObject $webCatalogRepository */
        $webCatalogRepository = $this->getMockBuilder(WebCatalogRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $webCatalogRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([$webCatalog]);

        $this->assertProcessCalled($webCatalogRepository, $webCatalog, $rootNode);

        $this->assertEquals(WebCatalogCacheProcessor::ACK, $this->processor->process($message, $session));
    }

    /**
     * @param WebCatalogRepository|\PHPUnit\Framework\MockObject\MockObject $webCatalogRepository
     * @param WebCatalog $webCatalog
     * @param ContentNode $node
     */
    private function assertProcessCalled(
        $webCatalogRepository,
        WebCatalog $webCatalog,
        ContentNode $node
    ) {
        $this->assertRepositoryCalls($webCatalogRepository, $webCatalog, [$node]);

        $this->logger->expects($this->never())
            ->method($this->anything());

        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => 21]);
        $scopes = [$scope];
        $this->scopeMatcher->expects($this->once())
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
        $this->assertChildJobCreated($webCatalog, $scope);
    }

    /**
     * @param string $body
     * @return MessageInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createMessage($body)
    {
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($body);
        $message->expects($this->once())
            ->method('getMessageId')
            ->willReturn('mid-42');

        return $message;
    }

    /**
     * @param WebCatalogRepository|\PHPUnit\Framework\MockObject\MockObject $webCatalogRepository
     * @param WebCatalog $webCatalog
     * @param ContentNode[]|[] $nodes
     */
    private function assertRepositoryCalls($webCatalogRepository, WebCatalog $webCatalog, array $nodes = [])
    {
        /** @var ContentNodeRepository|\PHPUnit\Framework\MockObject\MockObject $contentNodeRepo */
        $contentNodeRepo = $this->getMockBuilder(ContentNodeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $contentNodeRepo->expects($this->any())
            ->method('findBy')
            ->with(['webCatalog' => $webCatalog])
            ->willReturn($nodes);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->withConsecutive(
                [WebCatalog::class],
                [ContentNode::class]
            )
            ->willReturnOnConsecutiveCalls(
                $webCatalogRepository,
                $contentNodeRepo
            );
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);
    }

    private function assertUniqueJobExecuted()
    {
        /** @var Job|\PHPUnit\Framework\MockObject\MockObject $job */
        $job = $this->getMockBuilder(Job::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jobRunner->expects($this->once())
            ->method('runUnique')
            ->willReturnCallback(
                function ($ownerId, $name, $closure) use ($job) {
                    $this->assertEquals('mid-42', $ownerId);
                    $this->assertEquals(Topics::CALCULATE_WEB_CATALOG_CACHE, $name);

                    return $closure($this->jobRunner, $job);
                }
            );
    }

    /**
     * @param WebCatalog $webCatalog
     * @param Scope $scope
     */
    private function assertChildJobCreated(WebCatalog $webCatalog, Scope $scope)
    {
        /** @var Job|\PHPUnit\Framework\MockObject\MockObject $job */
        $childJob = $this->getMockBuilder(Job::class)
            ->disableOriginalConstructor()
            ->getMock();
        $childJob->expects($this->once())
            ->method('getId')
            ->willReturn(123);
        $this->jobRunner->expects($this->once())
            ->method('createDelayed')
            ->willReturnCallback(
                function ($name, $closure) use ($childJob, $webCatalog, $scope) {
                    $this->assertEquals(
                        sprintf(
                            '%s:%s:%s',
                            Topics::CALCULATE_CONTENT_NODE_TREE_BY_SCOPE,
                            $webCatalog->getId(),
                            $scope->getId()
                        ),
                        $name
                    );

                    return $closure($this->jobRunner, $childJob);
                }
            );
    }
}
