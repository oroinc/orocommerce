<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Async\Visibility;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Driver\PDOException;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DatabaseExceptionHelper;
use Oro\Bundle\ProductBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Model\ProductMessageFactory;
use Oro\Bundle\VisibilityBundle\Async\Visibility\ProductProcessor;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\ProductCaseCacheBuilderInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class ProductProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var ProductMessageFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageFactory;

    /**
     * @var ProductCaseCacheBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cacheBuilder;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    /**
     * @var DatabaseExceptionHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $databaseExceptionHelper;

    /**
     * @var ProductProcessor
     */
    protected $visibilityProcessor;

    protected function setUp()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->messageFactory = $this->getMockBuilder(ProductMessageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cacheBuilder = $this->createMock(ProductCaseCacheBuilderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->databaseExceptionHelper = $this->getMockBuilder(DatabaseExceptionHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->visibilityProcessor = new ProductProcessor(
            $this->registry,
            $this->messageFactory,
            $this->logger,
            $this->cacheBuilder,
            $this->databaseExceptionHelper
        );
        $this->visibilityProcessor->setResolvedVisibilityClassName(ProductVisibilityResolved::class);
    }
    public function testProcess()
    {
        $data = ['test' => 42];
        $body = json_encode($data);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->never()))
            ->method('rollback');
        $em->expects(($this->once()))
            ->method('commit');
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ProductVisibilityResolved::class)
            ->willReturn($em);
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($body);
        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->createMock(SessionInterface::class);
        $product = new Product();
        $this->messageFactory->expects($this->once())
            ->method('getProductFromMessage')
            ->with($data)
            ->willReturn($product);
        $this->cacheBuilder->expects($this->once())
            ->method('productCategoryChanged')
            ->with($product);
        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->visibilityProcessor->process($message, $session)
        );
    }
    public function testProcessDeadlock()
    {
        /** @var PDOException $exception */
        $exception = $this->getMockBuilder(PDOException::class)
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('rollback');
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ProductVisibilityResolved::class)
            ->willReturn($em);
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->will($this->throwException($exception));
        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Product Visibility resolve by Product',
                ['exception' => $exception]
            );
        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->createMock(SessionInterface::class);
        $this->databaseExceptionHelper->expects($this->once())
            ->method('isDeadlock')
            ->willReturn(true);
        $this->assertEquals(
            MessageProcessorInterface::REQUEUE,
            $this->visibilityProcessor->process($message, $session)
        );
    }
    public function testProcessException()
    {
        $exception = new \Exception('Some error');
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('rollback');
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ProductVisibilityResolved::class)
            ->willReturn($em);
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->will($this->throwException($exception));
        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Product Visibility resolve by Product',
                ['exception' => $exception]
            );
        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->createMock(SessionInterface::class);
        $this->databaseExceptionHelper->expects($this->never())
            ->method('isDeadlock');
        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->visibilityProcessor->process($message, $session)
        );
    }
    public function testProcessReject()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('rollback');
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ProductVisibilityResolved::class)
            ->willReturn($em);
        $this->messageFactory->expects($this->once())
            ->method('getProductFromMessage')
            ->will($this->throwException(new InvalidArgumentException('Wrong message')));
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(json_encode([]));
        $this->logger->expects($this->once())
            ->method('error')
            ->with('Message is invalid: Wrong message. Original message: "[]"');
        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->createMock(SessionInterface::class);
        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->visibilityProcessor->process($message, $session)
        );
    }
    public function testSetResolvedVisibilityClassName()
    {
        $this->assertAttributeEquals(
            ProductVisibilityResolved::class,
            'resolvedVisibilityClassName',
            $this->visibilityProcessor
        );
    }
}
