<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Method;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentTermBundle\Method\PaymentTerm as PaymentTermMethod;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PaymentTermTest extends \PHPUnit_Framework_TestCase
{
    /** @var PaymentTermProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentTermProvider;

    /** @var PropertyAccessor|\PHPUnit_Framework_MockObject_MockObject */
    protected $propertyAccessor;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var PaymentTransaction */
    protected $paymentTransaction;

    /** @var PaymentTermMethod */
    protected $method;

    protected function setUp()
    {
        $this->paymentTermProvider = $this->getMockBuilder('Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->propertyAccessor = $this->getMockBuilder('Symfony\Component\PropertyAccess\PropertyAccessor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentTransaction = new PaymentTransaction();
        $this->paymentTransaction->setSuccessful(false);

        $this->method = new PaymentTermMethod(
            $this->paymentTermProvider,
            $this->propertyAccessor,
            $this->doctrineHelper
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

        $this->propertyAccessor->expects($this->never())
            ->method('setValue');

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
        $paymentTerm = new \stdClass();

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

        $this->propertyAccessor->expects($this->once())
            ->method('setValue')
            ->with($entity, 'paymentTerm', $paymentTerm);

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
            ->willReturn(new \stdClass());

        $this->propertyAccessor->expects($this->once())
            ->method('setValue')
            ->willThrowException(new NoSuchPropertyException());

        $this->assertEquals(
            [],
            $this->method->execute($this->paymentTransaction->getAction(), $this->paymentTransaction)
        );
        $this->assertFalse($this->paymentTransaction->isSuccessful());
    }

    public function testGetType()
    {
        $this->assertEquals('payment_term', $this->method->getType());
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
}
