<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Async\Visibility;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Exception\InvalidArgumentException;
use Oro\Bundle\VisibilityBundle\Async\Visibility\ProductProcessor;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Model\ProductMessageFactory;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\ProductCaseCacheBuilderInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class ProductProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var ProductMessageFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $messageFactory;

    /**
     * @var ProductCaseCacheBuilderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $cacheBuilder;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $logger;

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
        $this->visibilityProcessor = new ProductProcessor(
            $this->registry,
            $this->messageFactory,
            $this->logger,
            $this->cacheBuilder
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
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($body);
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
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
        /** @var DeadlockException $exception */
        $exception = $this->createMock(DeadlockException::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('rollback');
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ProductVisibilityResolved::class)
            ->willReturn($em);
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
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
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->createMock(SessionInterface::class);
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
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
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
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->createMock(SessionInterface::class);
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
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(json_encode([]));
        $this->logger->expects($this->once())
            ->method('error')
            ->with('Message is invalid: Wrong message');
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
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
