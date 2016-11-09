<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Async\Visibility;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DatabaseExceptionHelper;
use Oro\Bundle\ProductBundle\Model\ProductMessageHandler;
use Oro\Bundle\VisibilityBundle\Async\Visibility\CategoryVisibilityProcessor;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Model\MessageFactoryInterface;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\CacheBuilderInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Psr\Log\LoggerInterface;

class CategoryVisibilityProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;
    /**
     * @var MessageFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageFactory;
    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;
    /**
     * @var CacheBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cacheBuilder;
    /**
     * @var ProductMessageHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productMessageHandler;
    /**
     * @var DatabaseExceptionHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $databaseExceptionHelper;
    /**
     * @var CategoryVisibilityProcessor
     */
    protected $categoryVisibilityProcessor;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageFactory = $this->getMock(MessageFactoryInterface::class);
        $this->logger = $this->getMock(LoggerInterface::class);
        $this->cacheBuilder = $this->getMock(CacheBuilderInterface::class);
        $this->productMessageHandler = $this->getMockBuilder(ProductMessageHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->databaseExceptionHelper = $this->getMockBuilder(DatabaseExceptionHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryVisibilityProcessor = new CategoryVisibilityProcessor(
            $this->registry,
            $this->messageFactory,
            $this->logger,
            $this->cacheBuilder,
            $this->databaseExceptionHelper,
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
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->getMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($body);
        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->getMock(SessionInterface::class);
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
