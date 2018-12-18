<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Async\Visibility;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\VisibilityBundle\Async\Visibility\CategoryVisibilityProcessor;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Model\MessageFactoryInterface;
use Oro\Bundle\VisibilityBundle\Model\ProductMessageHandler;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\CacheBuilderInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CategoryVisibilityProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RegistryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var MessageFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $messageFactory;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $logger;

    /**
     * @var CacheBuilderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $cacheBuilder;

    /**
     * @var ProductMessageHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $productMessageHandler;

    /**
     * @var CategoryVisibilityProcessor
     */
    protected $categoryVisibilityProcessor;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageFactory = $this->createMock(MessageFactoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->cacheBuilder = $this->createMock(CacheBuilderInterface::class);
        $this->productMessageHandler = $this->getMockBuilder(ProductMessageHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryVisibilityProcessor = new CategoryVisibilityProcessor(
            $this->registry,
            $this->messageFactory,
            $this->logger,
            $this->cacheBuilder,
            $this->productMessageHandler
        );
        $this->categoryVisibilityProcessor->setResolvedVisibilityClassName(CategoryVisibilityResolved::class);
    }
    public function testProcess()
    {
        $data = ['test' => 42];
        $body = json_encode($data);
        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->never()))
            ->method('rollback');
        $em->expects($this->once())
            ->method('commit');
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(CategoryVisibilityResolved::class)
            ->willReturn($em);
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($body);
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->createMock(SessionInterface::class);
        $visibilityEntity = new CategoryVisibility();
        $this->messageFactory->expects($this->once())
            ->method('getEntityFromMessage')
            ->with($data)
            ->willReturn($visibilityEntity);
        $this->cacheBuilder->expects($this->once())
            ->method('resolveVisibilitySettings')
            ->with($visibilityEntity);
        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->categoryVisibilityProcessor->process($message, $session)
        );
    }
}
