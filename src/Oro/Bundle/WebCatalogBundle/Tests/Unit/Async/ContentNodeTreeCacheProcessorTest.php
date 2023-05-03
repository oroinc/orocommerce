<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Bundle\WebCatalogBundle\Async\ContentNodeTreeCacheProcessor;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateContentNodeTreeCacheTopic;
use Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeCacheDumper;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class ContentNodeTreeCacheProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;
    use LoggerAwareTraitTestTrait;

    private ContentNodeTreeCacheDumper|\PHPUnit\Framework\MockObject\MockObject $dumper;

    private JobRunner|\PHPUnit\Framework\MockObject\MockObject $jobRunner;

    private ContentNodeTreeCacheProcessor $processor;

    protected function setUp(): void
    {
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->dumper = $this->createMock(ContentNodeTreeCacheDumper::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->processor = new ContentNodeTreeCacheProcessor(
            $this->jobRunner,
            $this->dumper
        );
        $this->setUpLoggerMock($this->processor);
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals(
            [WebCatalogCalculateContentNodeTreeCacheTopic::getName()],
            ContentNodeTreeCacheProcessor::getSubscribedTopics()
        );
    }

    public function testProcess(): void
    {
        $nodeId = 2;
        $scopeId = 5;
        $scope = $this->getEntity(Scope::class, ['id' => $scopeId]);
        $node = $this->getEntity(ContentNode::class, ['id' => $nodeId]);

        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::any())
            ->method('getBody')
            ->willReturn([
                'jobId' => 1,
                'scope' => $scope,
                'contentNode' => $node,
            ]);
        $session = $this->createMock(SessionInterface::class);

        $this->loggerMock->expects(self::never())
            ->method('error');

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->willReturnCallback(function ($jobId, $callback) {
                $this->assertEquals(1, $jobId);
                return $callback($this->jobRunner);
            });

        $this->dumper->expects(self::once())
            ->method('dump')
            ->with(self::identicalTo($node), self::identicalTo($scope));

        self::assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $session));
    }
}
