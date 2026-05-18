<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Unit\DraftSession;

use Oro\Bundle\EntityExtendBundle\Test\ExtendedEntityTestTrait;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\RFPBundle\DraftSession\RequestProductAwareOrderLineItemDraftSynchronizer;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Component\DraftSession\Doctrine\EntityDraftSyncReferenceResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RequestProductAwareOrderLineItemDraftSynchronizerTest extends TestCase
{
    use ExtendedEntityTestTrait;

    private EntityDraftSyncReferenceResolver&MockObject $draftSyncReferenceResolver;

    private RequestProductAwareOrderLineItemDraftSynchronizer $synchronizer;

    #[\Override]
    protected function setUp(): void
    {
        $this->draftSyncReferenceResolver = $this->createMock(EntityDraftSyncReferenceResolver::class);

        $this->synchronizer = new RequestProductAwareOrderLineItemDraftSynchronizer(
            $this->draftSyncReferenceResolver,
        );
    }

    public function testSupportsReturnsTrueForOrderLineItem(): void
    {
        self::assertTrue($this->synchronizer->supports(OrderLineItem::class));
    }

    public function testSupportsReturnsFalseForOtherClass(): void
    {
        self::assertFalse($this->synchronizer->supports(RequestProduct::class));
    }

    public function testSynchronizeFromDraftSetsRequestProductOnEntity(): void
    {
        $requestProduct = new RequestProduct();
        $resolvedReference = new RequestProduct();

        $draft = new OrderLineItem();
        $entity = new OrderLineItem();

        $requestProductValues = [];
        $requestProductValues[spl_object_id($draft)] = $requestProduct;

        $this->entityFieldTestExtension->addExpectation(
            OrderLineItem::class,
            'getRequestProduct',
            static function (array $arguments, object $object, mixed &$result) use (&$requestProductValues): bool {
                $result = $requestProductValues[spl_object_id($object)] ?? null;

                return true;
            }
        );
        $this->entityFieldTestExtension->addExpectation(
            OrderLineItem::class,
            'setRequestProduct',
            static function (array $arguments, object $object, mixed &$result) use (&$requestProductValues): bool {
                $requestProductValues[spl_object_id($object)] = $arguments[0];
                $result = $object;

                return true;
            }
        );

        $this->draftSyncReferenceResolver
            ->expects(self::once())
            ->method('getReference')
            ->with($requestProduct)
            ->willReturn($resolvedReference);

        $this->synchronizer->synchronizeFromDraft($draft, $entity);

        self::assertSame($resolvedReference, $requestProductValues[spl_object_id($entity)]);
    }

    public function testSynchronizeToDraftSetsRequestProductOnDraft(): void
    {
        $requestProduct = new RequestProduct();
        $resolvedReference = new RequestProduct();

        $entity = new OrderLineItem();
        $draft = new OrderLineItem();

        $requestProductValues = [];
        $requestProductValues[spl_object_id($entity)] = $requestProduct;

        $this->entityFieldTestExtension->addExpectation(
            OrderLineItem::class,
            'getRequestProduct',
            static function (array $arguments, object $object, mixed &$result) use (&$requestProductValues): bool {
                $result = $requestProductValues[spl_object_id($object)] ?? null;

                return true;
            }
        );
        $this->entityFieldTestExtension->addExpectation(
            OrderLineItem::class,
            'setRequestProduct',
            static function (array $arguments, object $object, mixed &$result) use (&$requestProductValues): bool {
                $requestProductValues[spl_object_id($object)] = $arguments[0];
                $result = $object;

                return true;
            }
        );

        $this->draftSyncReferenceResolver
            ->expects(self::once())
            ->method('getReference')
            ->with($requestProduct)
            ->willReturn($resolvedReference);

        $this->synchronizer->synchronizeToDraft($entity, $draft);

        self::assertSame($resolvedReference, $requestProductValues[spl_object_id($draft)]);
    }
}
