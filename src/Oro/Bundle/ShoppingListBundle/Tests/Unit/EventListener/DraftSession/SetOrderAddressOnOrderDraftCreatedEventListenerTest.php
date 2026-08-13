<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener\DraftSession;

use Oro\Bundle\CustomerBundle\Entity\AbstractDefaultTypedAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Bundle\OrderBundle\Manager\TypedOrderAddressCollection;
use Oro\Bundle\OrderBundle\Provider\OrderAddressProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\EventListener\DraftSession\SetOrderAddressOnOrderDraftCreatedEventListener;
use Oro\Component\DraftSession\Doctrine\EntityDraftSyncReferenceResolver;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Event\EntityDraftCreatedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SetOrderAddressOnOrderDraftCreatedEventListenerTest extends TestCase
{
    private EntityDraftSyncReferenceResolver&MockObject $draftSyncReferenceResolver;

    private OrderAddressManager&MockObject $orderAddressManager;

    private SetOrderAddressOnOrderDraftCreatedEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->draftSyncReferenceResolver = $this->createMock(EntityDraftSyncReferenceResolver::class);
        $this->orderAddressManager = $this->createMock(OrderAddressManager::class);

        $this->listener = new SetOrderAddressOnOrderDraftCreatedEventListener(
            $this->draftSyncReferenceResolver,
            $this->orderAddressManager,
        );
    }

    public function testOnEntityDraftCreatedIgnoresWhenEntityIsNotShoppingList(): void
    {
        $event = new EntityDraftCreatedEvent(
            $this->createMock(EntityDraftAwareInterface::class),
            new Order()
        );

        $this->orderAddressManager->expects(self::never())
            ->method('getGroupedAddresses');

        $this->listener->onEntityDraftCreated($event);
    }

    public function testOnEntityDraftCreatedIgnoresWhenDraftIsNotOrder(): void
    {
        $event = new EntityDraftCreatedEvent(
            new ShoppingList(),
            $this->createMock(EntityDraftAwareInterface::class)
        );

        $this->orderAddressManager->expects(self::never())
            ->method('getGroupedAddresses');

        $this->listener->onEntityDraftCreated($event);
    }

    public function testOnEntityDraftCreatedWhenNoDefaultAddresses(): void
    {
        $orderDraft = new Order();

        $billingCollection = $this->createMock(TypedOrderAddressCollection::class);
        $billingCollection->expects(self::once())
            ->method('getDefaultAddress')
            ->willReturn(null);

        $shippingCollection = $this->createMock(TypedOrderAddressCollection::class);
        $shippingCollection->expects(self::once())
            ->method('getDefaultAddress')
            ->willReturn(null);

        $this->orderAddressManager->expects(self::exactly(2))
            ->method('getGroupedAddresses')
            ->withConsecutive(
                [$orderDraft, OrderAddressProvider::ADDRESS_TYPE_BILLING],
                [$orderDraft, OrderAddressProvider::ADDRESS_TYPE_SHIPPING]
            )
            ->willReturnOnConsecutiveCalls($billingCollection, $shippingCollection);

        $this->orderAddressManager->expects(self::never())
            ->method('updateFromAbstract');

        $event = new EntityDraftCreatedEvent(new ShoppingList(), $orderDraft);

        $this->listener->onEntityDraftCreated($event);

        self::assertNull($orderDraft->getBillingAddress());
        self::assertNull($orderDraft->getShippingAddress());
    }

    public function testOnEntityDraftCreatedSetsBillingAndShippingAddresses(): void
    {
        $draftSessionUuid = 'draft-session-uuid';

        $orderDraft = new Order();
        $orderDraft->setDraftSessionUuid($draftSessionUuid);

        $defaultBillingAddress = $this->createMock(AbstractDefaultTypedAddress::class);
        $defaultShippingAddress = $this->createMock(AbstractDefaultTypedAddress::class);

        $billingCollection = $this->createMock(TypedOrderAddressCollection::class);
        $billingCollection->expects(self::once())
            ->method('getDefaultAddress')
            ->willReturn($defaultBillingAddress);

        $shippingCollection = $this->createMock(TypedOrderAddressCollection::class);
        $shippingCollection->expects(self::once())
            ->method('getDefaultAddress')
            ->willReturn($defaultShippingAddress);

        $this->orderAddressManager->expects(self::exactly(2))
            ->method('getGroupedAddresses')
            ->withConsecutive(
                [$orderDraft, OrderAddressProvider::ADDRESS_TYPE_BILLING],
                [$orderDraft, OrderAddressProvider::ADDRESS_TYPE_SHIPPING]
            )
            ->willReturnOnConsecutiveCalls($billingCollection, $shippingCollection);

        $billingCustomerAddress = new CustomerAddress();
        $billingCustomerUserAddress = new CustomerUserAddress();
        $billingAddress = new OrderAddress();
        $billingAddress->setCustomerAddress($billingCustomerAddress);
        $billingAddress->setCustomerUserAddress($billingCustomerUserAddress);

        $shippingCustomerAddress = new CustomerAddress();
        $shippingCustomerUserAddress = new CustomerUserAddress();
        $shippingAddress = new OrderAddress();
        $shippingAddress->setCustomerAddress($shippingCustomerAddress);
        $shippingAddress->setCustomerUserAddress($shippingCustomerUserAddress);

        $this->orderAddressManager->expects(self::exactly(2))
            ->method('updateFromAbstract')
            ->withConsecutive([$defaultBillingAddress], [$defaultShippingAddress])
            ->willReturnOnConsecutiveCalls($billingAddress, $shippingAddress);

        $resolvedBillingCustomerAddress = new CustomerAddress();
        $resolvedBillingCustomerUserAddress = new CustomerUserAddress();
        $resolvedShippingCustomerAddress = new CustomerAddress();
        $resolvedShippingCustomerUserAddress = new CustomerUserAddress();

        $this->draftSyncReferenceResolver->expects(self::exactly(4))
            ->method('getReference')
            ->willReturnMap([
                [$billingCustomerAddress, $resolvedBillingCustomerAddress],
                [$billingCustomerUserAddress, $resolvedBillingCustomerUserAddress],
                [$shippingCustomerAddress, $resolvedShippingCustomerAddress],
                [$shippingCustomerUserAddress, $resolvedShippingCustomerUserAddress],
            ]);

        $event = new EntityDraftCreatedEvent(new ShoppingList(), $orderDraft);

        $this->listener->onEntityDraftCreated($event);

        self::assertSame($billingAddress, $orderDraft->getBillingAddress());
        self::assertSame($shippingAddress, $orderDraft->getShippingAddress());

        self::assertSame($resolvedBillingCustomerAddress, $billingAddress->getCustomerAddress());
        self::assertSame($resolvedBillingCustomerUserAddress, $billingAddress->getCustomerUserAddress());
        self::assertSame($draftSessionUuid, $billingAddress->getDraftSessionUuid());

        self::assertSame($resolvedShippingCustomerAddress, $shippingAddress->getCustomerAddress());
        self::assertSame($resolvedShippingCustomerUserAddress, $shippingAddress->getCustomerUserAddress());
        self::assertSame($draftSessionUuid, $shippingAddress->getDraftSessionUuid());
    }
}
