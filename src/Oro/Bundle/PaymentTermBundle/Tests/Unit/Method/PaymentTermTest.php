<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Method;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Method\PaymentTerm as PaymentTermMethod;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PaymentTermTest extends \PHPUnit_Framework_TestCase
{
    /** @var PaymentTermProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentTermProvider;

    /** @var PaymentTermAssociationProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentTermAssociationProvider;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var PaymentTransaction */
    protected $paymentTransaction;

    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject  */
    protected $logger;

    /** @var PaymentTermMethod */
    protected $method;

    protected function setUp()
    {
        $this->paymentTermProvider = $this->getMockBuilder('Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentTermAssociationProvider = $this
            ->getMockBuilder('Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentTransaction = new PaymentTransaction();
        $this->paymentTransaction->setSuccessful(false);

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->method = new PaymentTermMethod(
            $this->paymentTermProvider,
            $this->paymentTermAssociationProvider,
            $this->doctrineHelper,
            $this->logger
        );
    }

    public function testExecuteNoEntity()
    {
        $entityClass = 'TestClass';
        $entityId = 10;

        $this->paymentTransaction
            ->setEntityClass($entityClass)
            ->setEntityIdentifier($entityId);

        $this->assertFalse($this->paymentTransaction->isSuccessful());

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with($entityClass, $entityId)
            ->willReturn(null);

        $this->paymentTermProvider->expects($this->never())
            ->method('getCurrentPaymentTerm');

        $this->assertEquals(
            [],
            $this->method->execute($this->paymentTransaction->getAction(), $this->paymentTransaction)
        );
        $this->assertFalse($this->paymentTransaction->isSuccessful());
    }

    public function testExecuteNoPaymentTerm()
    {
        $entity = new \stdClass();
        $entityClass = 'TestClass';
        $entityId = 10;

        $this->paymentTransaction
            ->setEntityClass($entityClass)
            ->setEntityIdentifier($entityId);

        $this->assertFalse($this->paymentTransaction->isSuccessful());

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with($entityClass, $entityId)
            ->willReturn($entity);

        $this->paymentTermProvider->expects($this->once())
            ->method('getCurrentPaymentTerm')
            ->willReturn(null);

        $this->paymentTermAssociationProvider->expects($this->never())
            ->method('setPaymentTerm');

        $this->assertEquals(
            [],
            $this->method->execute($this->paymentTransaction->getAction(), $this->paymentTransaction)
        );
        $this->assertFalse($this->paymentTransaction->isSuccessful());
    }

    public function testExecute()
    {
        $entityClass = 'TestClass';
        $entityId = 10;
        $entity = new \stdClass();
        $paymentTerm = new PaymentTerm();

        $this->paymentTransaction
            ->setEntityClass($entityClass)
            ->setEntityIdentifier($entityId);

        $this->assertFalse($this->paymentTransaction->isSuccessful());

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with($entityClass, $entityId)
            ->willReturn($entity);

        $this->paymentTermProvider->expects($this->once())
            ->method('getCurrentPaymentTerm')
            ->willReturn($paymentTerm);

        $this->paymentTermAssociationProvider->expects($this->once())
            ->method('setPaymentTerm')
            ->with($entity, $paymentTerm);

        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager->expects($this->once())
            ->method('flush')
            ->with($entity);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with($entity)
            ->willReturn($entityManager);

        $this->assertEquals(
            [],
            $this->method->execute($this->paymentTransaction->getAction(), $this->paymentTransaction)
        );
        $this->assertTrue($this->paymentTransaction->isSuccessful());
    }

    public function testExecuteEntityWithoutPaymentTerm()
    {
        $this->paymentTransaction
            ->setEntityClass('\stdClass')
            ->setEntityIdentifier(1);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->willReturn(new \stdClass());

        $this->paymentTermProvider->expects($this->once())
            ->method('getCurrentPaymentTerm')
            ->willReturn(new PaymentTerm());

        $this->paymentTermAssociationProvider->expects($this->once())
            ->method('setPaymentTerm')
            ->willThrowException(new NoSuchPropertyException());

        $this->logger->expects($this->once())
            ->method('error');

        $this->assertEquals(
            [],
            $this->method->execute($this->paymentTransaction->getAction(), $this->paymentTransaction)
        );
        $this->assertFalse($this->paymentTransaction->isSuccessful());
    }

    public function testGetType()
    {
        $this->assertEquals('payment_term', $this->method->getIdentifier());
    }

    /**
     * @param bool $expected
     * @param string $actionName
     *
     * @dataProvider supportsDataProvider
     */
    public function testSupports($expected, $actionName)
    {
        $this->assertEquals($expected, $this->method->supports($actionName));
    }

    /**
     * @return array
     */
    public function supportsDataProvider()
    {
        return [
            [false, PaymentTermMethod::AUTHORIZE],
            [false, PaymentTermMethod::CAPTURE],
            [false, PaymentTermMethod::CHARGE],
            [false, PaymentTermMethod::VALIDATE],
            [true, PaymentTermMethod::PURCHASE],
        ];
    }

    public function testIsApplicable()
    {
        $customer = $this->createMock(Account::class);

        /** @var PaymentContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock(PaymentContextInterface::class);
        $context->expects(static::any())
            ->method('getCustomer')
            ->willReturn($customer);

        $this->paymentTermProvider->expects($this->once())
            ->method('getPaymentTerm')
            ->with($customer)
            ->willReturn(new PaymentTerm());

        $this->assertTrue($this->method->isApplicable($context));
    }

    public function testIsApplicableFalse()
    {
        $customer = $this->createMock(Account::class);

        /** @var PaymentContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock(PaymentContextInterface::class);
        $context->expects(static::any())
            ->method('getCustomer')
            ->willReturn($customer);

        $this->paymentTermProvider->expects($this->once())
            ->method('getPaymentTerm')
            ->with($customer)
            ->willReturn(null);

        $this->assertFalse($this->method->isApplicable($context));
    }

    public function testIsApplicableNullCustomer()
    {
        /** @var PaymentContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock(PaymentContextInterface::class);
        $context->expects(static::any())
            ->method('getCustomer')
            ->willReturn(null);

        $this->paymentTermProvider->expects($this->never())
            ->method('getPaymentTerm');

        $this->assertFalse($this->method->isApplicable($context));
    }
}
