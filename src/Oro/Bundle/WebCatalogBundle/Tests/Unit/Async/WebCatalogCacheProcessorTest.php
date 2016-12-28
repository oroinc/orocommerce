<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Async\Topics;
use Oro\Bundle\WebCatalogBundle\Async\WebCatalogCacheProcessor;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ScopeMatcher;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Test\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

class WebCatalogCacheProcessorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var JobRunner|\PHPUnit_Framework_MockObject_MockObject
     */
    private $jobRunner;

    /**
     * @var MessageProducerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $producer;

    /**
     * @var ScopeMatcher|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeMatcher;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $registry;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var WebCatalogCacheProcessor
     */
    private $processor;

    protected function setUp()
    {
        $this->jobRunner = new JobRunner();
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
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        $message->expects($this->any())
            ->method('getBody')
            ->willReturn('1');

        $e = new \Exception('Test exception');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('find')
            ->willThrowException($e);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(WebCatalog::class)
            ->willReturn($em);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during queue message processing',
                [
                    'message' => $message->getBody(),
                    'topic' => Topics::CALCULATE_WEB_CATALOG_CACHE,
                    'exception' => $e
                ]
            );

        $this->assertEquals(WebCatalogCacheProcessor::REJECT, $this->processor->process($message, $session));
    }

    public function testProcess()
    {
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        $message->expects($this->any())
            ->method('getBody')
            ->willReturn('1');

        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => 1]);
        $rootNode = $this->getEntity(ContentNode::class, ['id' => 2]);

        /** @var ContentNodeRepository|\PHPUnit_Framework_MockObject_MockObject $contentNodeRepo */
        $contentNodeRepo = $this->getMockBuilder(ContentNodeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $contentNodeRepo->expects($this->any())
            ->method('getRootNodeByWebCatalog')
            ->with($webCatalog)
            ->willReturn($rootNode);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('find')
            ->with(WebCatalog::class, 1)
            ->willReturn($webCatalog);
        $em->expects($this->any())
            ->method('getRepository')
            ->with(ContentNode::class)
            ->willReturn($contentNodeRepo);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->logger->expects($this->never())
            ->method($this->anything());

        $scope1 = $this->getEntity(Scope::class, ['id' => 21]);
        $scopes = [
            $scope1
        ];

        $this->scopeMatcher->expects($this->once())
            ->method('getUsedScopes')
            ->with($webCatalog)
            ->willReturn($scopes);

        $this->producer->expects($this->once())
            ->method('send')
            ->with(
                Topics::CALCULATE_CONTENT_NODE_TREE_BY_SCOPE,
                [
                    'contentNode' => $rootNode->getId(),
                    'scope' => $scope1->getId(),
                    'jobId' => null
                ]
            );

        $this->assertEquals(WebCatalogCacheProcessor::ACK, $this->processor->process($message, $session));

        $uniqueJobs = $this->jobRunner->getRunUniqueJobs();
        $this->assertCount(1, $uniqueJobs);
        $this->assertArrayHasKey('jobName', $uniqueJobs[0]);
        $this->assertEquals(Topics::CALCULATE_WEB_CATALOG_CACHE, $uniqueJobs[0]['jobName']);

        $createdJobs = $this->jobRunner->getCreateDelayedJobs();
        $this->assertCount(1, $createdJobs);
        $this->assertArrayHasKey('jobName', $createdJobs[0]);
        $this->assertEquals(
            sprintf(
                '%s:%s:%s',
                Topics::CALCULATE_CONTENT_NODE_TREE_BY_SCOPE,
                $webCatalog->getId(),
                $scope1->getId()
            ),
            $createdJobs[0]['jobName']
        );
    }
}
