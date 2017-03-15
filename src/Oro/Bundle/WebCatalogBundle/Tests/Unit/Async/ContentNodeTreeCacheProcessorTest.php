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
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);

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

    /**
     * @dataProvider invalidMessageDataProvider
     * @param array $messageData
     */
    public function testShouldRejectOnInvalidMessage(array $messageData)
    {
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(JSON::encode($messageData));
        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during queue message processing'
            );

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('find')
            ->willReturn(new \stdClass());
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->jobRunner
            ->expects($this->never())
            ->method('runDelayed');

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $session));
    }

    /**
     * @return array
     */
    public function invalidMessageDataProvider()
    {
        return [
            'no scope' => [
                [
                    'jobId' => 2,
                    'contentNode' => 1,
                ]
            ],
            'no content node' => [
                [
                    'jobId' => 1,
                    'scope' => 2
                ]
            ],
            'no jobId' => [
                [
                    'contentNode' => 1,
                    'scope' => 2
                ]
            ],
            'incorrect job id type' => [
                [
                    'jobId' => 'a',
                    'contentNode' => 1,
                    'scope' => 2
                ]
            ],
            'incorrect scope type' => [
                [
                    'jobId' => 1,
                    'contentNode' => 2,
                    'scope' => 'a'
                ]
            ],
            'incorrect contentNode type' => [
                [
                    'jobId' => 1,
                    'contentNode' => 'a',
                    'scope' => 3
                ]
            ]
        ];
    }

    public function testShouldProcessMessageIfAllRequiredInfoAvailable()
    {
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(
                JSON::encode([
                    'jobId' => 1,
                    'scope' => 2,
                    'contentNode' => 3
                ])
            );
        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        $this->logger
            ->expects($this->never())
            ->method('error');

        $this->jobRunner->expects($this->once())
            ->method('runDelayed')
            ->willReturnCallback(function ($jobId, $callback) {
                $this->assertEquals(1, $jobId);
                return $callback($this->jobRunner);
            });

        $scope = new Scope();
        $node = new ContentNode();
        $this->configureEntityManager($scope, 2, $node, 3);

        $this->dumper->expects($this->once())
            ->method('dump')
            ->with($node, $scope);

        $this->assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $session));
    }

    public function testShouldCatchAndLogException()
    {
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(
                JSON::encode([
                    'jobId' => 1,
                    'scope' => 2,
                    'contentNode' => 3
                ])
            );
        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        $this->jobRunner->expects($this->once())
            ->method('runDelayed')
            ->willReturnCallback(function ($jobId, $callback) {
                $this->assertEquals(1, $jobId);
                return $callback($this->jobRunner);
            });

        $scope = new Scope();
        $node = new ContentNode();
        $this->configureEntityManager($scope, 2, $node, 3);

        $this->dumper->expects($this->once())
            ->method('dump')
            ->willThrowException(new \Exception('Test exception'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during queue message processing'
            );

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $session));
    }

    /**
     * @param Scope $scope
     * @param int $scopeId
     * @param ContentNode $node
     * @param int $nodeId
     */
    protected function configureEntityManager(Scope $scope, $scopeId, ContentNode $node, $nodeId)
    {
        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $scopeObjectManager */
        $scopeObjectManager = $this->createMock(EntityManagerInterface::class);
        $scopeObjectManager->expects($this->any())
            ->method('find')
            ->with(Scope::class, $scopeId)
            ->willReturn($scope);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $nodeObjectManager */
        $nodeObjectManager = $this->createMock(EntityManagerInterface::class);
        $nodeObjectManager->expects($this->any())
            ->method('find')
            ->with(ContentNode::class, $nodeId)
            ->willReturn($node);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->withConsecutive(
                [Scope::class],
                [ContentNode::class]
            )
            ->willReturnOnConsecutiveCalls(
                $scopeObjectManager,
                $nodeObjectManager
            );
    }
}
