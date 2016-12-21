<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Async\ContentNodeTreeCacheProcessor;
use Oro\Bundle\WebCatalogBundle\Async\Topics;
use Oro\Bundle\WebCatalogBundle\Cache\Dumper\ContentNodeTreeDumper;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class ContentNodeTreeCacheProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContentNodeTreeDumper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dumper;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $registry;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var JobRunner|\PHPUnit_Framework_MockObject_MockObject
     */
    private $jobRunner;

    /**
     * @var ContentNodeTreeCacheProcessor
     */
    private $processor;

    protected function setUp()
    {
        $this->jobRunner = $this->getMockBuilder(JobRunner::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dumper = $this->getMockBuilder(ContentNodeTreeDumper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->logger = $this->getMock(LoggerInterface::class);

        $this->processor = new ContentNodeTreeCacheProcessor(
            $this->jobRunner,
            $this->dumper,
            $this->registry,
            $this->logger
        );
    }

    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals(
            [Topics::CALCULATE_CONTENT_NODE_TREE_BY_SCOPE],
            ContentNodeTreeCacheProcessor::getSubscribedTopics()
        );
    }

    public function testShouldRejectMessageIfScopeIsEmpty()
    {
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message */
        $message = $this->getMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(
                JSON::encode([
                    'jobId' => 12345,
                    'contentNode' => 1,
                ])
            );
        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session */
        $session = $this->getMock(SessionInterface::class);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Message is invalid. Key "scope" was not found.',
                ['message' => $message->getBody()]
            );

        $this->jobRunner
            ->expects($this->once())
            ->method('runDelayed')
            ->with(12345)
            ->will($this->returnCallback(function ($name, $callback) {
                return $callback($this->jobRunner);
            }));

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $session));
    }

    public function testShouldRejectMessageIfContentNodeIsEmpty()
    {
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message */
        $message = $this->getMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(
                JSON::encode([
                    'jobId' => 12345,
                    'scope' => 12345
                ])
            );
        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session */
        $session = $this->getMock(SessionInterface::class);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Message is invalid. Key "contentNode" was not found.',
                ['message' => $message->getBody()]
            );

        $this->jobRunner
            ->expects($this->once())
            ->method('runDelayed')
            ->with(12345)
            ->will($this->returnCallback(function ($name, $callback) {
                return $callback($this->jobRunner);
            }));

        $scope = new Scope();
        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('find')
            ->with(Scope::class, 12345)
            ->willReturn($scope);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Scope::class)
            ->willReturn($em);

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $session));
    }

    public function testShouldProcessMessageIfAllRequiredInfoAvailable()
    {
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message */
        $message = $this->getMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(
                JSON::encode([
                    'jobId' => 12345,
                    'scope' => 12345,
                    'contentNode' => 12345
                ])
            );
        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session */
        $session = $this->getMock(SessionInterface::class);

        $this->logger
            ->expects($this->never())
            ->method('error');

        $this->jobRunner
            ->expects($this->once())
            ->method('runDelayed')
            ->with(12345)
            ->will($this->returnCallback(function ($name, $callback) {
                return $callback($this->jobRunner);
            }));

        $scope = new Scope();
        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $scopeObjectManager */
        $scopeObjectManager = $this->getMock(EntityManagerInterface::class);
        $scopeObjectManager->expects($this->any())
            ->method('find')
            ->with(Scope::class, 12345)
            ->willReturn($scope);

        $node = new ContentNode();
        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $nodeObjectManager */
        $nodeObjectManager = $this->getMock(EntityManagerInterface::class);
        $nodeObjectManager->expects($this->any())
            ->method('find')
            ->with(ContentNode::class, 12345)
            ->willReturn($node);

        $this->registry->method('getManagerForClass')
            ->withConsecutive(
                [Scope::class],
                [ContentNode::class]
            )
            ->willReturnOnConsecutiveCalls(
                $scopeObjectManager,
                $nodeObjectManager
            );

        $this->dumper->expects($this->once())
            ->method('dump')
            ->with($node, $scope);

        $this->assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $session));
    }

    public function testShouldCatchAndLogException()
    {
        $exception = new \Exception('Some error');

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message */
        $message = $this->getMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(
                JSON::encode([
                    'jobId' => 12345,
                    'scope' => 12345,
                    'contentNode' => 12345
                ])
            );
        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session */
        $session = $this->getMock(SessionInterface::class);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during queue message processing',
                [
                    'message' => $message->getBody(),
                    'topic' => Topics::CALCULATE_CONTENT_NODE_TREE_BY_SCOPE,
                    'exception' => $exception
                ]
            );

        $this->jobRunner
            ->expects($this->once())
            ->method('runDelayed')
            ->with(12345)
            ->will($this->returnCallback(function ($name, $callback) {
                return $callback($this->jobRunner);
            }));

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('find')
            ->will($this->throwException($exception));
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Scope::class)
            ->willReturn($em);

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $session));
    }
}
