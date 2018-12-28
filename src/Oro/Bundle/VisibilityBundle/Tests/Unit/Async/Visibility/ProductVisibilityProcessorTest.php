<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Async\Visibility;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Driver\AbstractDriverException;
use Doctrine\DBAL\Driver\PDOException;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\VisibilityBundle\Async\Visibility\ProductVisibilityProcessor;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\VisibilityBundle\Model\MessageFactoryInterface;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\CacheBuilderInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class ProductVisibilityProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var MessageFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $messageFactory;

    /**
     * @var CacheBuilderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $cacheBuilder;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $logger;

    /**
     * @var ProductVisibilityProcessor
     */
    protected $visibilityProcessor;

    protected function setUp()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->messageFactory = $this->getMockBuilder(MessageFactoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cacheBuilder = $this->createMock(CacheBuilderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->visibilityProcessor = new ProductVisibilityProcessor(
            $this->registry,
            $this->messageFactory,
            $this->logger,
            $this->cacheBuilder
        );

        $this->visibilityProcessor->setResolvedVisibilityClassName(ProductVisibilityResolved::class);
    }

    public function testProcessInvalidArgumentException()
    {
        $data = ['test' => 42];
        $body = json_encode($data);

        $message = $this->prepareTestProcess($body);

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session * */
        $session = $this->createMock(SessionInterface::class);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                sprintf(
                    'Message is invalid: %s',
                    'Test message'
                )
            );

        $this->messageFactory->expects($this->once())
            ->method('getEntityFromMessage')
            ->with($data)
            ->willThrowException(new InvalidArgumentException('Test message'));

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->visibilityProcessor->process($message, $session)
        );
    }

    public function testProcessDeadlock()
    {
        /** @var DeadlockException $exception */
        $exception = $this->createMock(DeadlockException::class);

        $data = ['test' => 42];
        $body = json_encode($data);

        $message = $this->prepareTestProcess($body);

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session * */
        $session = $this->createMock(SessionInterface::class);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Product Visibility resolve',
                ['exception' => $exception]
            );

        $visibilityEntity = new ProductVisibility();

        $this->messageFactory->expects($this->once())
            ->method('getEntityFromMessage')
            ->with($data)
            ->willReturn($visibilityEntity);

        $this->cacheBuilder->expects($this->once())
            ->method('resolveVisibilitySettings')
            ->with($visibilityEntity)
            ->willThrowException($exception);

        $driverException = $this->createMock(AbstractDriverException::class);

        $this->assertEquals(
            MessageProcessorInterface::REQUEUE,
            $this->visibilityProcessor->process($message, $session)
        );
    }

    public function testProcessException()
    {
        $exception = new \Exception('Exception message');

        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('beginTransaction');

        $em->expects(($this->once()))
            ->method('rollback');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ProductVisibilityResolved::class)
            ->willReturn($em);

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message * */
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->will($this->throwException($exception));

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Product Visibility resolve',
                ['exception' => $exception]
            );

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session * */
        $session = $this->createMock(SessionInterface::class);

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->visibilityProcessor->process($message, $session)
        );
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

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message * */
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($body);

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session * */
        $session = $this->createMock(SessionInterface::class);

        $visibilityEntity = new ProductVisibility();

        $this->messageFactory->expects($this->once())
            ->method('getEntityFromMessage')
            ->with($data)
            ->willReturn($visibilityEntity);

        $this->cacheBuilder->expects($this->once())
            ->method('resolveVisibilitySettings')
            ->with($visibilityEntity);
        $this->assertEquals(
            MessageProcessorInterface::ACK,
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

    /**
     * @param string $body
     * @return MessageInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function prepareTestProcess($body)
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('beginTransaction');

        $em->expects(($this->once()))
            ->method('rollback');

        $em->expects(($this->never()))
            ->method('commit');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ProductVisibilityResolved::class)
            ->willReturn($em);

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message * */
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($body);

        return $message;
    }
}
