<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Async\Visibility;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\VisibilityBundle\Async\Visibility\CustomerProcessor;
use Oro\Bundle\VisibilityBundle\Driver\CustomerPartialUpdateDriverInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class CustomerProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var CustomerPartialUpdateDriverInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $partialUpdateDriver;

    /** @var CustomerProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->partialUpdateDriver = $this->createMock(CustomerPartialUpdateDriverInterface::class);

        $this->processor = new CustomerProcessor(
            $this->doctrine,
            $this->logger,
            $this->partialUpdateDriver
        );
    }

    /**
     * @param mixed $body
     *
     * @return MessageInterface
     */
    private function getMessage($body): MessageInterface
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn(JSON::encode($body));

        return $message;
    }

    private function getSession(): SessionInterface
    {
        return $this->createMock(SessionInterface::class);
    }

    public function testProcessWithInvalidMessage()
    {
        $this->logger->expects($this->once())
            ->method('critical')
            ->with('Got invalid message.');

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage('invalid'), $this->getSession())
        );
    }

    public function testProcessWithEmptyMessage()
    {
        $this->logger->expects($this->once())
            ->method('critical')
            ->with('Got invalid message.');

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage([]), $this->getSession())
        );
    }

    public function testProcess()
    {
        $body = ['id' => 1];

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->never()))
            ->method('rollback');
        $em->expects(($this->once()))
            ->method('commit');

        $this->doctrine->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [BaseVisibilityResolved::class, $em],
                [Customer::class, $em]
            ]);

        $customer = new Customer();
        $em->expects($this->once())
            ->method('find')
            ->with(Customer::class, $body['id'])
            ->willReturn($customer);
        $this->partialUpdateDriver->expects($this->once())
            ->method('updateCustomerVisibility')
            ->with($this->identicalTo($customer));

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessWhenCustomerNotFound()
    {
        $body = ['id' => 1];

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('rollback');
        $em->expects(($this->never()))
            ->method('commit');

        $this->doctrine->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [BaseVisibilityResolved::class, $em],
                [Customer::class, $em]
            ]);

        $em->expects($this->once())
            ->method('find')
            ->with(Customer::class, $body['id'])
            ->willReturn(null);
        $this->partialUpdateDriver->expects($this->never())
            ->method('updateCustomerVisibility');

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during update Customer Visibility.',
                ['exception' => new EntityNotFoundException('Customer was not found.')]
            );

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessException()
    {
        $body = ['id' => 1];

        $exception = new \Exception('some error');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('rollback');
        $em->expects(($this->never()))
            ->method('commit');

        $this->doctrine->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [BaseVisibilityResolved::class, $em],
                [Customer::class, $em]
            ]);

        $this->logger->expects($this->once())
            ->method('error');

        $customer = new Customer();
        $em->expects($this->once())
            ->method('find')
            ->with(Customer::class, $body['id'])
            ->willReturn($customer);
        $this->partialUpdateDriver->expects($this->once())
            ->method('updateCustomerVisibility')
            ->with($this->identicalTo($customer))
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during update Customer Visibility.',
                ['exception' => $exception]
            );

        $this->assertEquals(
            MessageProcessorInterface::REQUEUE,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessUniqieException()
    {
        $body = ['id' => 1];

        $exception = $this->createMock(UniqueConstraintViolationException::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('rollback');
        $em->expects(($this->never()))
            ->method('commit');

        $this->doctrine->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [BaseVisibilityResolved::class, $em],
                [Customer::class, $em]
            ]);

        $this->logger->expects($this->once())
            ->method('warning');

        $customer = new Customer();
        $em->expects($this->once())
            ->method('find')
            ->with(Customer::class, $body['id'])
            ->willReturn($customer);
        $this->partialUpdateDriver->expects($this->once())
            ->method('updateCustomerVisibility')
            ->with($this->identicalTo($customer))
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Couldn`t create scope because the scope already created with the same data.',
                ['exception' => $exception]
            );

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }
}
