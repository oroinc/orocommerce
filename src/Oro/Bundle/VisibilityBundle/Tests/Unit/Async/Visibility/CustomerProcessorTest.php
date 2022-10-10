<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Async\Visibility;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Bundle\VisibilityBundle\Async\Visibility\CustomerProcessor;
use Oro\Bundle\VisibilityBundle\Driver\CustomerPartialUpdateDriverInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class CustomerProcessorTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $doctrine;

    private CustomerPartialUpdateDriverInterface|\PHPUnit\Framework\MockObject\MockObject $partialUpdateDriver;

    private CustomerProcessor $processor;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->partialUpdateDriver = $this->createMock(CustomerPartialUpdateDriverInterface::class);

        $this->processor = new CustomerProcessor($this->doctrine, $this->partialUpdateDriver);
        $this->setUpLoggerMock($this->processor);
    }

    private function getMessage(array $body): MessageInterface
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn($body);

        return $message;
    }

    private function getSession(): SessionInterface
    {
        return $this->createMock(SessionInterface::class);
    }

    public function testProcess(): void
    {
        $body = ['id' => 1];

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects((self::never()))
            ->method('rollback');
        $em->expects((self::once()))
            ->method('commit');

        $this->doctrine->expects(self::exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [BaseVisibilityResolved::class, $em],
                [Customer::class, $em],
            ]);

        $customer = new Customer();
        $em->expects(self::once())
            ->method('find')
            ->with(Customer::class, $body['id'])
            ->willReturn($customer);
        $this->partialUpdateDriver->expects(self::once())
            ->method('updateCustomerVisibility')
            ->with(self::identicalTo($customer));

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessWhenCustomerNotFound(): void
    {
        $body = ['id' => 1];

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects((self::once()))
            ->method('rollback');
        $em->expects((self::never()))
            ->method('commit');

        $this->doctrine->expects(self::exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [BaseVisibilityResolved::class, $em],
                [Customer::class, $em],
            ]);

        $em->expects(self::once())
            ->method('find')
            ->with(Customer::class, $body['id'])
            ->willReturn(null);
        $this->partialUpdateDriver->expects(self::never())
            ->method('updateCustomerVisibility');

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during update Customer Visibility.',
                ['exception' => new EntityNotFoundException('Customer was not found.')]
            );

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessException(): void
    {
        $body = ['id' => 1];

        $exception = new \Exception('some error');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects((self::once()))
            ->method('rollback');
        $em->expects((self::never()))
            ->method('commit');

        $this->doctrine->expects(self::exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [BaseVisibilityResolved::class, $em],
                [Customer::class, $em],
            ]);

        $this->loggerMock->expects(self::once())
            ->method('error');

        $customer = new Customer();
        $em->expects(self::once())
            ->method('find')
            ->with(Customer::class, $body['id'])
            ->willReturn($customer);
        $this->partialUpdateDriver->expects(self::once())
            ->method('updateCustomerVisibility')
            ->with(self::identicalTo($customer))
            ->willThrowException($exception);

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during update Customer Visibility.',
                ['exception' => $exception]
            );

        self::assertEquals(
            MessageProcessorInterface::REQUEUE,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessUniqieException(): void
    {
        $body = ['id' => 1];

        $exception = $this->createMock(UniqueConstraintViolationException::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects((self::once()))
            ->method('rollback');
        $em->expects((self::never()))
            ->method('commit');

        $this->doctrine->expects(self::exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [BaseVisibilityResolved::class, $em],
                [Customer::class, $em],
            ]);

        $this->loggerMock->expects(self::once())
            ->method('warning');

        $customer = new Customer();
        $em->expects(self::once())
            ->method('find')
            ->with(Customer::class, $body['id'])
            ->willReturn($customer);
        $this->partialUpdateDriver->expects(self::once())
            ->method('updateCustomerVisibility')
            ->with(self::identicalTo($customer))
            ->willThrowException($exception);

        $this->loggerMock->expects(self::once())
            ->method('warning')
            ->with(
                'Couldn`t create scope because the scope already created with the same data.',
                ['exception' => $exception]
            );

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }
}
