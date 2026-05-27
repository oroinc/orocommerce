<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Unit\EventListener\DraftSession;

use Oro\Bundle\CustomerBundle\Entity\AbstractDefaultTypedAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Bundle\OrderBundle\Manager\TypedOrderAddressCollection;
use Oro\Bundle\OrderBundle\Provider\OrderAddressProvider;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\EventListener\DraftSession\SetOrderAddressOnOrderDraftCreatedEventListener;
use Oro\Component\DraftSession\Doctrine\EntityDraftSyncReferenceResolver;
use Oro\Component\DraftSession\Event\EntityDraftCreatedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SetOrderAddressOnOrderDraftCreatedEventListenerTest extends TestCase
{
    private OrderAddressManager&MockObject $orderAddressManager;

    private EntityDraftSyncReferenceResolver&MockObject $draftSyncReferenceResolver;

    private SetOrderAddressOnOrderDraftCreatedEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->orderAddressManager = $this->createMock(OrderAddressManager::class);
        $this->draftSyncReferenceResolver = $this->createMock(EntityDraftSyncReferenceResolver::class);

        $this->listener = new SetOrderAddressOnOrderDraftCreatedEventListener(
            $this->draftSyncReferenceResolver,
            $this->orderAddressManager,
        );
    }

    public function testOnEntityDraftCreatedIgnoresWhenEntityIsNotRequest(): void
    {
        $event = $this->createMock(EntityDraftCreatedEvent::class);
        $event
            ->method('getEntity')
            ->willReturn(new Order());
        $event
            ->method('getDraft')
            ->willReturn(new Order());

        $this->orderAddressManager
            ->expects(self::never())
            ->method('getGroupedAddresses');

        $this->listener->onEntityDraftCreated($event);
    }

    public function testOnEntityDraftCreatedIgnoresWhenDraftIsNotOrder(): void
    {
        $event = $this->createMock(EntityDraftCreatedEvent::class);
        $event
            ->method('getEntity')
            ->willReturn(new Request());
        $event
            ->method('getDraft')
            ->willReturn(new OrderLineItem());

        $this->orderAddressManager
            ->expects(self::never())
            ->method('getGroupedAddresses');

        $this->listener->onEntityDraftCreated($event);
    }

    public function testOnEntityDraftCreatedSetsBillingAndShippingAddress(): void
    {
        $orderDraft = new Order();
        $orderDraft->setDraftSessionUuid('order-draft-uuid');

        $billingAddress = new OrderAddress();
        $shippingAddress = new OrderAddress();

        $sourceAddress = $this->createMock(AbstractDefaultTypedAddress::class);

        $billingCollection = $this->createMock(TypedOrderAddressCollection::class);
        $billingCollection
            ->expects(self::once())
            ->method('getDefaultAddress')
            ->willReturn($sourceAddress);

        $shippingCollection = $this->createMock(TypedOrderAddressCollection::class);
        $shippingCollection
            ->expects(self::once())
            ->method('getDefaultAddress')
            ->willReturn($sourceAddress);

        $this->orderAddressManager
            ->expects(self::exactly(2))
            ->method('getGroupedAddresses')
            ->willReturnOnConsecutiveCalls($billingCollection, $shippingCollection);

        $this->orderAddressManager
            ->expects(self::exactly(2))
            ->method('updateFromAbstract')
            ->willReturnOnConsecutiveCalls($billingAddress, $shippingAddress);

        $this->draftSyncReferenceResolver
            ->method('getReference')
            ->willReturnCallback(static fn (?object $entity): ?object => $entity);

        $event = new EntityDraftCreatedEvent(new Request(), $orderDraft);

        $this->listener->onEntityDraftCreated($event);

        self::assertSame($billingAddress, $orderDraft->getBillingAddress());
        self::assertSame($shippingAddress, $orderDraft->getShippingAddress());
        self::assertSame('order-draft-uuid', $orderDraft->getBillingAddress()->getDraftSessionUuid());
        self::assertSame('order-draft-uuid', $orderDraft->getShippingAddress()->getDraftSessionUuid());
    }

    public function testOnEntityDraftCreatedSkipsAddressWhenNoDefaultAddressAvailable(): void
    {
        $orderDraft = new Order();

        $this->orderAddressManager
            ->expects(self::exactly(2))
            ->method('getGroupedAddresses')
            ->willReturnOnConsecutiveCalls(
                new TypedOrderAddressCollection(null, OrderAddressProvider::ADDRESS_TYPE_BILLING),
                new TypedOrderAddressCollection(null, OrderAddressProvider::ADDRESS_TYPE_SHIPPING)
            );

        $this->orderAddressManager
            ->expects(self::never())
            ->method('updateFromAbstract');

        $event = new EntityDraftCreatedEvent(new Request(), $orderDraft);

        $this->listener->onEntityDraftCreated($event);

        self::assertNull($orderDraft->getBillingAddress());
        self::assertNull($orderDraft->getShippingAddress());
    }

    public function testOnEntityDraftCreatedUsesResolverForAddressReferences(): void
    {
        $orderDraft = new Order();
        $billingAddress = new OrderAddress();
        $shippingAddress = new OrderAddress();

        $billingCustomerAddress = new CustomerAddress();
        $shippingCustomerAddress = new CustomerAddress();
        $billingCustomerUserAddress = new CustomerUserAddress();
        $shippingCustomerUserAddress = new CustomerUserAddress();

        $billingCustomerAddressReference = new CustomerAddress();
        $shippingCustomerAddressReference = new CustomerAddress();
        $billingCustomerUserAddressReference = new CustomerUserAddress();
        $shippingCustomerUserAddressReference = new CustomerUserAddress();

        $billingAddress->setCustomerAddress($billingCustomerAddress);
        $shippingAddress->setCustomerAddress($shippingCustomerAddress);
        $billingAddress->setCustomerUserAddress($billingCustomerUserAddress);
        $shippingAddress->setCustomerUserAddress($shippingCustomerUserAddress);

        $sourceAddress = $this->createMock(AbstractDefaultTypedAddress::class);

        $billingCollection = $this->createMock(TypedOrderAddressCollection::class);
        $billingCollection
            ->method('getDefaultAddress')
            ->willReturn($sourceAddress);

        $shippingCollection = $this->createMock(TypedOrderAddressCollection::class);
        $shippingCollection
            ->method('getDefaultAddress')
            ->willReturn($sourceAddress);

        $this->orderAddressManager
            ->expects(self::exactly(2))
            ->method('getGroupedAddresses')
            ->willReturnOnConsecutiveCalls($billingCollection, $shippingCollection);

        $this->orderAddressManager
            ->expects(self::exactly(2))
            ->method('updateFromAbstract')
            ->willReturnOnConsecutiveCalls($billingAddress, $shippingAddress);

        $this->draftSyncReferenceResolver
            ->expects(self::exactly(4))
            ->method('getReference')
            ->willReturnMap([
                [$billingCustomerAddress, $billingCustomerAddressReference],
                [$billingCustomerUserAddress, $billingCustomerUserAddressReference],
                [$shippingCustomerAddress, $shippingCustomerAddressReference],
                [$shippingCustomerUserAddress, $shippingCustomerUserAddressReference],
            ]);

        $event = new EntityDraftCreatedEvent(new Request(), $orderDraft);

        $this->listener->onEntityDraftCreated($event);

        self::assertSame($billingAddress, $orderDraft->getBillingAddress());
        self::assertSame($shippingAddress, $orderDraft->getShippingAddress());
        self::assertSame($billingCustomerAddressReference, $orderDraft->getBillingAddress()?->getCustomerAddress());
        self::assertSame(
            $billingCustomerUserAddressReference,
            $orderDraft->getBillingAddress()?->getCustomerUserAddress()
        );
        self::assertSame($shippingCustomerAddressReference, $orderDraft->getShippingAddress()?->getCustomerAddress());
        self::assertSame(
            $shippingCustomerUserAddressReference,
            $orderDraft->getShippingAddress()?->getCustomerUserAddress()
        );
    }
}
