<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Async;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Async\ContentNodeTreeCacheProcessor;
use Oro\Bundle\WebCatalogBundle\Async\Topics;
use Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeCacheDumper;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

class ContentNodeTreeCacheProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ContentNodeTreeCacheDumper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dumper;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var JobRunner|\PHPUnit\Framework\MockObject\MockObject
     */
    private $jobRunner;

    /**
     * @var ContentNodeTreeCacheProcessor
     */
    private $processor;

    /**
     * @var CacheProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $layoutCacheProvider;

    protected function setUp(): void
    {
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->dumper = $this->createMock(ContentNodeTreeCacheDumper::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->layoutCacheProvider = $this->createMock(CacheProvider::class);

        $this->processor = new ContentNodeTreeCacheProcessor(
            $this->jobRunner,
            $this->dumper,
            $this->registry,
            $this->logger,
            $this->layoutCacheProvider
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
     */
    public function testShouldRejectOnInvalidMessage(array $messageData)
    {
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(JSON::encode($messageData));
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Unexpected exception occurred during queue message processing');

        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('find')
            ->willReturn(new \stdClass());
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->jobRunner->expects($this->never())
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
        $nodeId = 2;
        $scopeId = 5;

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(
                JSON::encode([
                    'jobId' => 1,
                    'scope' => $scopeId,
                    'contentNode' => $nodeId
                ])
            );
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        $this->logger->expects($this->never())
            ->method('error');

        $this->jobRunner->expects($this->once())
            ->method('runDelayed')
            ->willReturnCallback(function ($jobId, $callback) {
                $this->assertEquals(1, $jobId);
                return $callback($this->jobRunner);
            });

        [$scope, $node] = $this->configureEntityManager($scopeId, $nodeId);

        $this->dumper->expects($this->once())
            ->method('dump')
            ->with($this->identicalTo($node), $this->identicalTo($scope));

        $this->layoutCacheProvider->expects($this->once())
            ->method('deleteAll');

        $this->assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $session));
    }

    public function testShouldCatchAndLogException()
    {
        $nodeId = 2;
        $scopeId = 5;

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(
                JSON::encode([
                    'jobId' => 1,
                    'scope' => $scopeId,
                    'contentNode' => $nodeId
                ])
            );
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        $this->jobRunner->expects($this->once())
            ->method('runDelayed')
            ->willReturnCallback(function ($jobId, $callback) {
                $this->assertEquals(1, $jobId);
                return $callback($this->jobRunner);
            });

        $this->configureEntityManager($scopeId, $nodeId);

        $this->dumper->expects($this->once())
            ->method('dump')
            ->willThrowException(new \Exception('Test exception'));

        $this->layoutCacheProvider->expects($this->never())->method('deleteAll');

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Unexpected exception occurred during queue message processing');

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $session));
    }

    /**
     * @param int $scopeId
     * @param int $nodeId
     *
     * @return array [scope, $node]
     */
    protected function configureEntityManager(int $scopeId, int $nodeId): array
    {
        $scope = $this->getEntity(Scope::class, ['id' => $scopeId]);
        $node = $this->getEntity(ContentNode::class, ['id' => $nodeId]);

        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $scopeObjectManager */
        $scopeObjectManager = $this->createMock(EntityManagerInterface::class);
        $scopeObjectManager->expects($this->any())
            ->method('find')
            ->with(Scope::class, $scopeId)
            ->willReturn($scope);

        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $nodeObjectManager */
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

        return [$scope, $node];
    }
}
