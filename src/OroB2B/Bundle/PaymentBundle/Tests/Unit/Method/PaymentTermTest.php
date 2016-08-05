<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Method;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PaymentBundle\Method\Config\PaymentTermConfigInterface;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Method\PaymentTerm as PaymentTermMethod;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider;

/**
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

    /** @var PaymentTermConfigInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $config;

    protected function setUp()
    {
        $this->paymentTermProvider = $this->getMockBuilder('OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->propertyAccessor = $this->getMockBuilder('Symfony\Component\PropertyAccess\PropertyAccessor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->config = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\Config\PaymentTermConfigInterface');

        $this->paymentTransaction = new PaymentTransaction();
        $this->paymentTransaction->setSuccessful(false);

        $this->method = new PaymentTermMethod(
            $this->paymentTermProvider,
            $this->config,
            $this->propertyAccessor,
            $this->doctrineHelper
        );
    }

    protected function tearDown()
    {
        unset(
            $this->method,
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

    /**
     * @dataProvider isEnabledProvider
     * @param bool $configValue
     * @param bool $expected
     */
    public function testIsEnabled($configValue, $expected)
    {
        $this->config->expects($this->once())
            ->method('isEnabled')
            ->willReturn($configValue);

        $this->assertEquals($expected, $this->method->isEnabled());
    }

    /**
     * @return array
     */
    public function isEnabledProvider()
    {
        return [
            [
                'configValue' => true,
                'expected' => true,
            ],
            [
                'configValue' => false,
                'expected' => false,
            ],
        ];
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

    public function testIsApplicable()
    {
        $this->config->expects($this->once())
            ->method('isCountryApplicable')
            ->willReturn(true);

        $this->config->expects($this->once())
            ->method('isCurrencyApplicable')
            ->willReturn(true);

        $this->paymentTermProvider->expects($this->once())
            ->method('getCurrentPaymentTerm')
            ->willReturn(new PaymentTerm());

        $this->assertTrue($this->method->isApplicable(['currency' => 'USD']));
    }

    public function testIsApplicableWithoutCountry()
    {
        $this->config->expects($this->once())
            ->method('isCountryApplicable')
            ->willReturn(false);

        $this->config->expects($this->never())
            ->method('isCurrencyApplicable');

        $this->paymentTermProvider->expects($this->never())->method('getCurrentPaymentTerm');

        $this->assertFalse($this->method->isApplicable(['country' => 'US']));
    }

    public function testIsApplicableWithoutCurrentPaymentTerm()
    {
        $this->config->expects($this->once())
            ->method('isCountryApplicable')
            ->willReturn(true);

        $this->config->expects($this->never())
            ->method('isCurrencyApplicable');

        $this->paymentTermProvider->expects($this->once())
            ->method('getCurrentPaymentTerm')
            ->willReturn(null);

        $this->assertFalse($this->method->isApplicable([]));
    }

    public function testRequiresVerification()
    {
        $this->assertFalse($this->method->requiresVerification());
    }
}
