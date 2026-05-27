<?php

declare(strict_types=1);

namespace Oro\Bundle\TaxBundle\Tests\Unit\DraftSession;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\TaxBundle\DraftSession\TaxCodeAwareOrderLineItemDraftSynchronizer;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Tests\Unit\Stub\OrderLineItemStub;
use Oro\Component\DraftSession\Doctrine\EntityDraftSyncReferenceResolver;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class TaxCodeAwareOrderLineItemDraftSynchronizerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private EntityManagerInterface&MockObject $entityManager;
    private TaxCodeAwareOrderLineItemDraftSynchronizer $synchronizer;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $referenceResolver = new EntityDraftSyncReferenceResolver($this->doctrine);

        $this->synchronizer = new TaxCodeAwareOrderLineItemDraftSynchronizer($referenceResolver);
    }

    public function testSupportsOrderLineItemClass(): void
    {
        self::assertTrue($this->synchronizer->supports(OrderLineItem::class));
    }

    public function testDoesNotSupportOtherClass(): void
    {
        self::assertFalse($this->synchronizer->supports(\stdClass::class));
    }

    public function testSynchronizeFromDraftCopiesTaxCode(): void
    {
        $taxCode = new ProductTaxCode();
        ReflectionUtil::setId($taxCode, 100);

        $draft = new OrderLineItemStub();
        $draft->setFreeFormTaxCode($taxCode);

        $entity = new OrderLineItemStub();

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(ProductTaxCode::class)
            ->willReturn($this->entityManager);
        $this->entityManager->expects(self::once())
            ->method('contains')
            ->with($taxCode)
            ->willReturn(true);

        $this->synchronizer->synchronizeFromDraft($draft, $entity);

        self::assertSame($taxCode, $entity->getFreeFormTaxCode());
    }

    public function testSynchronizeToDraftCopiesTaxCode(): void
    {
        $taxCode = new ProductTaxCode();
        ReflectionUtil::setId($taxCode, 100);

        $entity = new OrderLineItemStub();
        $entity->setFreeFormTaxCode($taxCode);

        $draft = new OrderLineItemStub();

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(ProductTaxCode::class)
            ->willReturn($this->entityManager);
        $this->entityManager->expects(self::once())
            ->method('contains')
            ->with($taxCode)
            ->willReturn(true);

        $this->synchronizer->synchronizeToDraft($entity, $draft);

        self::assertSame($taxCode, $draft->getFreeFormTaxCode());
    }

    public function testSynchronizeFromDraftSetsNullWhenSourceTaxCodeIsNull(): void
    {
        $oldTaxCode = new ProductTaxCode();
        ReflectionUtil::setId($oldTaxCode, 600);

        $draft = new OrderLineItemStub();
        $draft->setFreeFormTaxCode(null);

        $entity = new OrderLineItemStub();
        $entity->setFreeFormTaxCode($oldTaxCode);

        $this->synchronizer->synchronizeFromDraft($draft, $entity);

        self::assertNull($entity->getFreeFormTaxCode());
    }

    public function testSynchronizeFromDraftGetsReferenceWhenEntityNotManaged(): void
    {
        $taxCode = new ProductTaxCode();
        ReflectionUtil::setId($taxCode, 200);

        $taxCodeReference = new ProductTaxCode();
        ReflectionUtil::setId($taxCodeReference, 200);

        $draft = new OrderLineItemStub();
        $draft->setFreeFormTaxCode($taxCode);

        $entity = new OrderLineItemStub();

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with($taxCode)
            ->willReturn([200]);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(ProductTaxCode::class)
            ->willReturn($this->entityManager);
        $this->entityManager->expects(self::once())
            ->method('contains')
            ->with($taxCode)
            ->willReturn(false);
        $this->entityManager->expects(self::once())
            ->method('getClassMetadata')
            ->with(ProductTaxCode::class)
            ->willReturn($classMetadata);
        $this->entityManager->expects(self::once())
            ->method('getReference')
            ->with(ProductTaxCode::class, 200)
            ->willReturn($taxCodeReference);

        $this->synchronizer->synchronizeFromDraft($draft, $entity);

        self::assertSame($taxCodeReference, $entity->getFreeFormTaxCode());
    }

    public function testSynchronizeFromDraftReplacesExistingTaxCode(): void
    {
        $oldTaxCode = new ProductTaxCode();
        ReflectionUtil::setId($oldTaxCode, 400);

        $newTaxCode = new ProductTaxCode();
        ReflectionUtil::setId($newTaxCode, 500);

        $draft = new OrderLineItemStub();
        $draft->setFreeFormTaxCode($newTaxCode);

        $entity = new OrderLineItemStub();
        $entity->setFreeFormTaxCode($oldTaxCode);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(ProductTaxCode::class)
            ->willReturn($this->entityManager);
        $this->entityManager->expects(self::once())
            ->method('contains')
            ->with($newTaxCode)
            ->willReturn(true);

        self::assertSame($oldTaxCode, $entity->getFreeFormTaxCode());

        $this->synchronizer->synchronizeFromDraft($draft, $entity);

        self::assertSame($newTaxCode, $entity->getFreeFormTaxCode());
        self::assertNotSame($oldTaxCode, $entity->getFreeFormTaxCode());
    }
}
