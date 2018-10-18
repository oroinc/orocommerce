<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Async\Visibility;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\VisibilityBundle\Async\Visibility\CustomerProcessor;
use Oro\Bundle\VisibilityBundle\Driver\CustomerPartialUpdateDriverInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Model\MessageFactoryInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class CustomerProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $logger;

    /**
     * @var MessageFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $messageFactory;

    /**
     * @var CustomerPartialUpdateDriverInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $driver;

    /**
     * @var CustomerProcessor
     */
    protected $processor;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();
        $this->messageFactory = $this->getMockBuilder(MessageFactoryInterface::class)
            ->getMock();
        $this->driver = $this->getMockBuilder(CustomerPartialUpdateDriverInterface::class)
            ->getMock();
        $this->processor = new CustomerProcessor(
            $this->doctrineHelper,
            $this->logger,
            $this->messageFactory,
            $this->driver
        );
    }

    public function testProcessWithCustomer()
    {
        $data = ['id' => 1];
        $body = JSON::encode($data);

        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->never()))
            ->method('rollback');
        $em->expects(($this->once()))
            ->method('commit');
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(BaseVisibilityResolved::class)
            ->willReturn($em);

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->getMockBuilder(MessageInterface::class)
            ->getMock();
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn($body);

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->getMockBuilder(SessionInterface::class)
            ->getMock();

        $customer = new Customer();

        $this->messageFactory->expects($this->once())
            ->method('getEntityFromMessage')
            ->with($data)
            ->willReturn($customer);

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $session)
        );
    }

    public function testProcessReject()
    {
        $data = ['test' => 1];
        $body = JSON::encode($data);

        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('rollback');
        $em->expects(($this->never()))
            ->method('commit');
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(BaseVisibilityResolved::class)
            ->willReturn($em);
        $this->logger->expects($this->once())
            ->method('error');

        $this->messageFactory->expects($this->once())
            ->method('getEntityFromMessage')
            ->with($data)
            ->willThrowException(new InvalidArgumentException());

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->getMockBuilder(MessageInterface::class)
            ->getMock();
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn($body);
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->getMockBuilder(SessionInterface::class)
            ->getMock();

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $session)
        );
    }

    public function testProcessRequeue()
    {
        $data = ['test' => 1];
        $body = JSON::encode($data);

        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('rollback');
        $em->expects(($this->never()))
            ->method('commit');
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(BaseVisibilityResolved::class)
            ->willReturn($em);
        $this->logger->expects($this->once())
            ->method('error');

        $customer = new Customer();
        $this->messageFactory->expects($this->once())
            ->method('getEntityFromMessage')
            ->with($data)
            ->willReturn($customer);
        $this->driver->expects($this->once())
            ->method('updateCustomerVisibility')
            ->with($customer)
            ->willThrowException(new \Exception());

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->getMockBuilder(MessageInterface::class)
            ->getMock();
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn($body);
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->getMockBuilder(SessionInterface::class)
            ->getMock();

        $this->assertEquals(
            MessageProcessorInterface::REQUEUE,
            $this->processor->process($message, $session)
        );
    }
}
