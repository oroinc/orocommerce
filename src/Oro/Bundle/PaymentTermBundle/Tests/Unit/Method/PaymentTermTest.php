<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Method;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;
use Oro\Bundle\PaymentTermBundle\Method\PaymentTerm as PaymentTermMethod;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PaymentTermTest extends \PHPUnit\Framework\TestCase
{
    /** @var PaymentTermProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentTermProvider;

    /** @var PaymentTermAssociationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentTermAssociationProvider;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var PaymentTransaction */
    private $paymentTransaction;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var PaymentTermMethod */
    private $method;

    protected function setUp(): void
    {
        $this->paymentTermProvider = $this->createMock(PaymentTermProvider::class);
        $this->paymentTermAssociationProvider = $this->createMock(PaymentTermAssociationProvider::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $paymentConfig = $this->createMock(PaymentTermConfigInterface::class);
        $paymentConfig->expects($this->any())
            ->method('getPaymentMethodIdentifier')
            ->willReturn('payment_term');

        $this->paymentTransaction = new PaymentTransaction();
        $this->paymentTransaction->setSuccessful(false);

        $this->method = new PaymentTermMethod(
            $this->paymentTermProvider,
            $this->paymentTermAssociationProvider,
            $this->doctrineHelper,
            $paymentConfig
        );
        $this->method->setLogger($this->logger);
    }

    public function testExecutePurchaseViaExecute()
    {
        $entityClass = 'TestClass';
        $entityId = 10;
        $entity = new \stdClass();
        $paymentTerm = new PaymentTerm();

        $this->paymentTransaction
            ->setAction(PaymentTermMethod::PURCHASE)
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

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->once())
            ->method('flush')
            ->with($entity);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with($entity)
            ->willReturn($entityManager);

        $this->assertEquals(
            ['successful' => true],
            $this->method->execute($this->paymentTransaction->getAction(), $this->paymentTransaction)
        );
        $this->assertTrue($this->paymentTransaction->isActive());
        $this->assertTrue($this->paymentTransaction->isSuccessful());
        $this->assertEquals(PaymentTermMethod::PENDING, $this->paymentTransaction->getAction());
    }

    public function testExecutePurchaseDirectly()
    {
        $entityClass = 'TestClass';
        $entityId = 10;
        $entity = new \stdClass();
        $paymentTerm = new PaymentTerm();

        $this->paymentTransaction
            ->setAction(PaymentTermMethod::PURCHASE)
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

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->once())
            ->method('flush')
            ->with($entity);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with($entity)
            ->willReturn($entityManager);

        $this->assertEquals(['successful' => true], $this->method->purchase($this->paymentTransaction));
        $this->assertTrue($this->paymentTransaction->isActive());
        $this->assertTrue($this->paymentTransaction->isSuccessful());
        $this->assertEquals(PaymentTermMethod::PENDING, $this->paymentTransaction->getAction());
    }

    public function testExecuteCaptureViaExecute()
    {
        $transaction = new PaymentTransaction();
        $this->assertFalse($transaction->isSuccessful());
        $this->assertFalse($transaction->isActive());

        $this->assertEquals(['successful' => true], $this->method->execute(PaymentTermMethod::CAPTURE, $transaction));
        $this->assertTrue($transaction->isActive());
        $this->assertTrue($transaction->isSuccessful());
        $this->assertEquals(PaymentTermMethod::CAPTURE, $transaction->getAction());
    }

    public function testExecuteCaptureDirectly()
    {
        $transaction = new PaymentTransaction();
        $this->assertFalse($transaction->isSuccessful());
        $this->assertFalse($transaction->isActive());

        $this->assertEquals(['successful' => true], $this->method->capture($transaction));
        $this->assertTrue($transaction->isActive());
        $this->assertTrue($transaction->isSuccessful());
        $this->assertEquals(PaymentTermMethod::CAPTURE, $transaction->getAction());
    }

    public function testExecuteNotSupported()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"payment_term" payment method "not_supported" action is not supported');

        $this->method->execute('not_supported', new PaymentTransaction());
    }

    public function testGetSourceAction()
    {
        $this->assertEquals('pending', $this->method->getSourceAction());
    }

    public function testuUseSourcePaymentTransaction()
    {
        $this->assertTrue($this->method->useSourcePaymentTransaction());
    }

    public function testExecuteNoEntity()
    {
        $entityClass = 'TestClass';
        $entityId = 10;

        $this->paymentTransaction
            ->setAction(PaymentTermMethod::PURCHASE)
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
            ['successful' => false],
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
            ->setAction(PaymentTermMethod::PURCHASE)
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
            ['successful' => false],
            $this->method->execute($this->paymentTransaction->getAction(), $this->paymentTransaction)
        );
        $this->assertFalse($this->paymentTransaction->isSuccessful());
    }

    public function testExecuteEntityWithoutPaymentTerm()
    {
        $this->paymentTransaction
            ->setAction(PaymentTermMethod::PURCHASE)
            ->setEntityClass(\stdClass::class)
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
            ['successful' => false],
            $this->method->execute($this->paymentTransaction->getAction(), $this->paymentTransaction)
        );
        $this->assertFalse($this->paymentTransaction->isSuccessful());
    }

    public function testGetType()
    {
        $this->assertEquals('payment_term', $this->method->getIdentifier());
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function testSupports(bool $expected, string $actionName)
    {
        $this->assertEquals($expected, $this->method->supports($actionName));
    }

    public function supportsDataProvider(): array
    {
        return [
            [false, PaymentTermMethod::AUTHORIZE],
            [true, PaymentTermMethod::CAPTURE],
            [false, PaymentTermMethod::CHARGE],
            [false, PaymentTermMethod::VALIDATE],
            [true, PaymentTermMethod::PURCHASE],
        ];
    }

    public function testIsApplicable()
    {
        $customer = $this->createMock(Customer::class);

        $context = $this->createMock(PaymentContextInterface::class);
        $context->expects(self::any())
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
        $customer = $this->createMock(Customer::class);

        $context = $this->createMock(PaymentContextInterface::class);
        $context->expects(self::any())
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
        $context = $this->createMock(PaymentContextInterface::class);
        $context->expects(self::any())
            ->method('getCustomer')
            ->willReturn(null);

        $this->paymentTermProvider->expects($this->never())
            ->method('getPaymentTerm');

        $this->assertFalse($this->method->isApplicable($context));
    }
}
