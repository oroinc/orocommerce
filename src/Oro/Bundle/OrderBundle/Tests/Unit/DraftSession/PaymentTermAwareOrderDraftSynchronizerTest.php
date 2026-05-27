<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\DraftSession;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\DraftSession\PaymentTermAwareOrderDraftSynchronizer;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Unit\Stub\OrderStub;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Component\DraftSession\Doctrine\EntityDraftSyncReferenceResolver;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class PaymentTermAwareOrderDraftSynchronizerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private EntityManagerInterface&MockObject $entityManager;
    private PaymentTermAssociationProvider&MockObject $paymentTermAssociationProvider;
    private PaymentTermAwareOrderDraftSynchronizer $synchronizer;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->paymentTermAssociationProvider = $this->createMock(PaymentTermAssociationProvider::class);

        $referenceResolver = new EntityDraftSyncReferenceResolver($this->doctrine);
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $this->synchronizer = new PaymentTermAwareOrderDraftSynchronizer(
            $referenceResolver,
            $this->paymentTermAssociationProvider,
            $propertyAccessor,
        );
    }

    public function testSupportsOrderClass(): void
    {
        self::assertTrue($this->synchronizer->supports(Order::class));
    }

    public function testDoesNotSupportOtherClass(): void
    {
        self::assertFalse($this->synchronizer->supports(\stdClass::class));
    }

    public function testSynchronizeFromDraftCopiesPaymentTerm(): void
    {
        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);
        $this->entityManager->expects(self::any())
            ->method('contains')
            ->willReturn(true);

        $paymentTerm = new PaymentTerm();
        ReflectionUtil::setId($paymentTerm, 10);

        $this->paymentTermAssociationProvider->expects(self::once())
            ->method('getAssociationNames')
            ->with(Order::class)
            ->willReturn(['paymentTerm']);

        $this->paymentTermAssociationProvider->expects(self::once())
            ->method('getPaymentTerm')
            ->willReturn($paymentTerm);

        $draft = new OrderStub();
        $draft->setPaymentTerm($paymentTerm);

        $entity = new OrderStub();

        $this->synchronizer->synchronizeFromDraft($draft, $entity);

        self::assertSame($paymentTerm, $entity->getPaymentTerm());
    }

    public function testSynchronizeToDraftCopiesPaymentTerm(): void
    {
        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);
        $this->entityManager->expects(self::any())
            ->method('contains')
            ->willReturn(true);

        $paymentTerm = new PaymentTerm();
        ReflectionUtil::setId($paymentTerm, 20);

        $this->paymentTermAssociationProvider->expects(self::once())
            ->method('getAssociationNames')
            ->with(Order::class)
            ->willReturn(['paymentTerm']);

        $this->paymentTermAssociationProvider->expects(self::once())
            ->method('getPaymentTerm')
            ->willReturn($paymentTerm);

        $entity = new OrderStub();
        $entity->setPaymentTerm($paymentTerm);

        $draft = new OrderStub();

        $this->synchronizer->synchronizeToDraft($entity, $draft);

        self::assertSame($paymentTerm, $draft->getPaymentTerm());
    }

    public function testSynchronizeFromDraftWithMultipleAssociations(): void
    {
        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);
        $this->entityManager->expects(self::any())
            ->method('contains')
            ->willReturn(true);

        $paymentTerm = new PaymentTerm();
        ReflectionUtil::setId($paymentTerm, 10);

        $customerPaymentTerm = new PaymentTerm();
        ReflectionUtil::setId($customerPaymentTerm, 20);

        $this->paymentTermAssociationProvider->expects(self::once())
            ->method('getAssociationNames')
            ->with(Order::class)
            ->willReturn(['paymentTerm', 'customerPaymentTerm']);

        $this->paymentTermAssociationProvider->expects(self::exactly(2))
            ->method('getPaymentTerm')
            ->willReturnOnConsecutiveCalls($paymentTerm, $customerPaymentTerm);

        $draft = new OrderStub();
        $draft->setPaymentTerm($paymentTerm);
        $draft->setCustomerPaymentTerm($customerPaymentTerm);

        $entity = new OrderStub();

        $this->synchronizer->synchronizeFromDraft($draft, $entity);

        self::assertSame($paymentTerm, $entity->getPaymentTerm());
        self::assertSame($customerPaymentTerm, $entity->getCustomerPaymentTerm());
    }

    public function testSynchronizeFromDraftCopiesNullPaymentTerm(): void
    {
        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);
        $this->entityManager->expects(self::any())
            ->method('contains')
            ->willReturn(true);

        $this->paymentTermAssociationProvider->expects(self::once())
            ->method('getAssociationNames')
            ->with(Order::class)
            ->willReturn(['paymentTerm']);

        $this->paymentTermAssociationProvider->expects(self::once())
            ->method('getPaymentTerm')
            ->willReturn(null);

        $draft = new OrderStub();
        $entity = new OrderStub();

        $this->synchronizer->synchronizeFromDraft($draft, $entity);

        self::assertNull($entity->getPaymentTerm());
    }
}
