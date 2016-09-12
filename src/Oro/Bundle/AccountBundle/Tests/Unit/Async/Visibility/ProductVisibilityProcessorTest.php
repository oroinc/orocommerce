<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Async\Visibility;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\AccountBundle\Async\Visibility\ProductVisibilityProcessor;
use Oro\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\AccountBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\AccountBundle\Model\ProductVisibilityMessageFactory;
use Oro\Bundle\AccountBundle\Visibility\Cache\CacheBuilderInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ProductVisibilityProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var ProductVisibilityMessageFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageFactory;

    /**
     * @var CacheBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cacheBuilder;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    /**
     * @var ProductVisibilityProcessor
     */
    protected $visibilityProcessor;

    protected function setUp()
    {
        $this->registry = $this->getMock(RegistryInterface::class);
        $this->messageFactory = $this->getMockBuilder(ProductVisibilityMessageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cacheBuilder = $this->getMock(CacheBuilderInterface::class);
        $this->logger = $this->getMock(LoggerInterface::class);

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

        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->once())
            ->method('beginTransaction');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ProductVisibilityResolved::class)
            ->willReturn($em);

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->getMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($body);

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->getMock(SessionInterface::class);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                sprintf(
                    'Message is invalid: %s. Original message: "%s"',
                    'Test message',
                    $body
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

    public function testProcessException()
    {
        $data = ['test' => 42];
        $body = json_encode($data);

        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->once())
            ->method('beginTransaction');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ProductVisibilityResolved::class)
            ->willReturn($em);

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->getMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($body);

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->getMock(SessionInterface::class);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                sprintf(
                    'Transaction aborted wit error: %s.',
                    'Exception message',
                    $body
                )
            );

        $visibilityEntity = new ProductVisibility();

        $this->messageFactory->expects($this->once())
            ->method('getEntityFromMessage')
            ->with($data)
            ->willReturn($visibilityEntity);

        $this->cacheBuilder->expects($this->once())
            ->method('resolveVisibilitySettings')
            ->with($visibilityEntity)
            ->willThrowException(new \Exception('Exception message'));

        $this->assertEquals(
            MessageProcessorInterface::REQUEUE,
            $this->visibilityProcessor->process($message, $session)
        );
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

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ProductVisibilityResolved::class)
            ->willReturn($em);

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->getMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($body);

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->getMock(SessionInterface::class);

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
}
