<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\WebCatalogBundle\Async\Topics;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Resolver\DefaultVariantScopesResolver;
use Oro\Bundle\WebCatalogBundle\Generator\SlugGenerator;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Bundle\WebCatalogBundle\Async\ContentNodeSlugsProcessor;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

class ContentNodeSlugsProcessorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var DefaultVariantScopesResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $defaultVariantScopesResolver;

    /**
     * @var SlugGenerator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $slugGenerator;

    /**
     * @var MessageProducerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageProducer;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    /**
     * @var ContentNodeSlugsProcessor
     */
    protected $processor;

    protected function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->defaultVariantScopesResolver = $this->getMockBuilder(DefaultVariantScopesResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->slugGenerator = $this->getMockBuilder(SlugGenerator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageProducer = $this->getMock(MessageProducerInterface::class);
        $this->logger = $this->getMock(LoggerInterface::class);
        $this->processor = new ContentNodeSlugsProcessor(
            $this->registry,
            $this->defaultVariantScopesResolver,
            $this->slugGenerator,
            $this->messageProducer,
            $this->logger
        );
    }

    public function testProcess()
    {
        $em = $this->getMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('beginTransaction');

        $em->expects($this->never())
            ->method('rollback');

        $em->expects($this->once())
            ->method('flush');

        $em->expects($this->once())
            ->method('commit');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ContentNode::class)
            ->willReturn($em);

        $contentNodeId = 42;
        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => 2]);
        $contentNode = $this->getEntity(ContentNode::class, ['id' => $contentNodeId, 'webCatalog' => $webCatalog]);

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->getMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn("{$contentNodeId}");

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->getMock(SessionInterface::class);

        $em->expects($this->once())
            ->method('find')
            ->with(ContentNode::class, $contentNodeId)
            ->willReturn($contentNode);

        $this->defaultVariantScopesResolver->expects($this->once())
            ->method('resolve')
            ->with($contentNode);

        $this->slugGenerator->expects($this->once())
            ->method('generate')
            ->with($contentNode);
        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with(Topics::CALCULATE_WEB_CATALOG_CACHE, 2);

        $this->assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $session));
    }
    
    public function testProcessWithException()
    {
        $em = $this->getMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('beginTransaction');

        $em->expects($this->once())
            ->method('rollback');

        $em->expects($this->never())
            ->method('commit');

        $this->logger->expects($this->once())
            ->method('error');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ContentNode::class)
            ->willReturn($em);

        $contentNodeId = 42;
        $contentNode = $this->getEntity(ContentNode::class, ['id' => $contentNodeId, 'webCatalog' => new WebCatalog()]);

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->getMock(MessageInterface::class);
        $message->expects($this->exactly(2))
            ->method('getBody')
            ->willReturn("{$contentNodeId}");

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->getMock(SessionInterface::class);

        $em->expects($this->once())
            ->method('find')
            ->with(ContentNode::class, $contentNodeId)
            ->willReturn($contentNode);

        $this->defaultVariantScopesResolver->expects($this->once())
            ->method('resolve')
            ->willThrowException(new \Exception());
        $this->messageProducer->expects($this->never())
            ->method('send');

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $session));
    }

    public function testGetSubscribedTopics()
    {
        $this->assertEquals([Topics::RESOLVE_NODE_SLUGS], $this->processor->getSubscribedTopics());
    }
}
