<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\DraftSession;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\DraftSession\OrderDraftSynchronizer;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Unit\Stub\OrderStub;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\DraftSession\Doctrine\EntityDraftSyncReferenceResolver;
use Oro\Component\DraftSession\Synchronizer\EntityDraftSynchronizerInterface;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OrderDraftSynchronizerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private EntityManagerInterface&MockObject $entityManager;
    private EntityDraftSynchronizerInterface&MockObject $entityDraftSynchronizer;
    private OrderDraftSynchronizer $synchronizer;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityDraftSynchronizer = $this->createMock(EntityDraftSynchronizerInterface::class);

        $referenceResolver = new EntityDraftSyncReferenceResolver($this->doctrine);

        $this->synchronizer = new OrderDraftSynchronizer(
            $referenceResolver,
            $this->entityDraftSynchronizer,
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

    public function testSynchronizeFromDraftAddsToDraftCollectionWhenOrderHasNoId(): void
    {
        $orderDraft = new OrderStub();
        ReflectionUtil::setId($orderDraft, 1400);

        $order = new OrderStub();

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);
        $this->entityManager->expects(self::any())
            ->method('contains')
            ->willReturn(true);

        self::assertNull($order->getId());
        self::assertCount(0, $order->getDrafts());

        $this->synchronizer->synchronizeFromDraft($orderDraft, $order);

        self::assertTrue($order->getDrafts()->contains($orderDraft));
        self::assertCount(1, $order->getDrafts());
    }

    public function testSynchronizeFromDraftDoesNotAddToDraftCollectionWhenOrderHasId(): void
    {
        $orderDraft = new OrderStub();
        ReflectionUtil::setId($orderDraft, 1500);

        $order = new OrderStub();
        ReflectionUtil::setId($order, 2000);

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);
        $this->entityManager->expects(self::any())
            ->method('contains')
            ->willReturn(true);

        self::assertCount(0, $order->getDrafts());

        $this->synchronizer->synchronizeFromDraft($orderDraft, $order);

        self::assertCount(0, $order->getDrafts());
    }

    public function testSynchronizeFromDraftCopiesFields(): void
    {
        $organization = new Organization();
        $owner = new User();
        $customer = new Customer();
        $customerUser = new CustomerUser();
        $website = new Website();
        $shipUntil = new \DateTime('2026-06-30');

        $orderDraft = new OrderStub();
        ReflectionUtil::setId($orderDraft, 300);
        $orderDraft->setOrganization($organization);
        $orderDraft->setOwner($owner);
        $orderDraft->setCustomer($customer);
        $orderDraft->setCustomerUser($customerUser);
        $orderDraft->setCurrency('EUR');
        $orderDraft->setWebsite($website);
        $orderDraft->setShippingMethod('flat_rate');
        $orderDraft->setShippingMethodType('per_order');
        $orderDraft->setEstimatedShippingCostAmount(15.50);
        $orderDraft->setOverriddenShippingCostAmount(12.00);
        $orderDraft->setPoNumber('PO-2026-001');
        $orderDraft->setShipUntil($shipUntil);
        $orderDraft->setCustomerNotes('Please deliver to back entrance');

        $order = new OrderStub();
        ReflectionUtil::setId($order, 100);

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);
        $this->entityManager->expects(self::any())
            ->method('contains')
            ->willReturn(true);

        $this->synchronizer->synchronizeFromDraft($orderDraft, $order);

        self::assertSame($organization, $order->getOrganization());
        self::assertSame($owner, $order->getOwner());
        self::assertSame($customer, $order->getCustomer());
        self::assertSame($customerUser, $order->getCustomerUser());
        self::assertEquals('EUR', $order->getCurrency());
        self::assertSame($website, $order->getWebsite());
        self::assertEquals('flat_rate', $order->getShippingMethod());
        self::assertEquals('per_order', $order->getShippingMethodType());
        self::assertEquals(15.50, $order->getEstimatedShippingCostAmount());
        self::assertEquals(12.00, $order->getOverriddenShippingCostAmount());
        self::assertEquals('PO-2026-001', $order->getPoNumber());
        self::assertNotNull($order->getShipUntil());
        self::assertEquals($shipUntil, $order->getShipUntil());
        self::assertEquals('Please deliver to back entrance', $order->getCustomerNotes());
    }

    public function testSynchronizeFromDraftSkipsOrganizationWhenNull(): void
    {
        $organization = new Organization();

        $orderDraft = new OrderStub();
        // No organization set on draft

        $order = new OrderStub();
        ReflectionUtil::setId($order, 100);
        $order->setOrganization($organization);

        $this->synchronizer->synchronizeFromDraft($orderDraft, $order);

        self::assertSame($organization, $order->getOrganization());
    }

    public function testSynchronizeFromDraftSkipsWebsiteWhenNull(): void
    {
        $website = new Website();

        $orderDraft = new OrderStub();
        // No website set on draft

        $order = new OrderStub();
        ReflectionUtil::setId($order, 100);
        $order->setWebsite($website);

        $this->synchronizer->synchronizeFromDraft($orderDraft, $order);

        self::assertSame($website, $order->getWebsite());
    }

    public function testSynchronizeFromDraftClearsShipUntilWhenNull(): void
    {
        $orderDraft = new OrderStub();
        // No shipUntil set on draft

        $order = new OrderStub();
        ReflectionUtil::setId($order, 100);
        $order->setShipUntil(new \DateTime('2026-01-01'));

        $this->synchronizer->synchronizeFromDraft($orderDraft, $order);

        self::assertNull($order->getShipUntil());
    }

    public function testSynchronizeFromDraftClonesShipUntil(): void
    {
        $shipUntil = new \DateTime('2026-09-15');

        $orderDraft = new OrderStub();
        ReflectionUtil::setId($orderDraft, 300);
        $orderDraft->setShipUntil($shipUntil);

        $order = new OrderStub();
        ReflectionUtil::setId($order, 100);

        $this->synchronizer->synchronizeFromDraft($orderDraft, $order);

        $resultShipUntil = $order->getShipUntil();
        self::assertNotNull($resultShipUntil);
        self::assertEquals($shipUntil, $resultShipUntil);
        self::assertNotSame($shipUntil, $resultShipUntil);
    }

    public function testSynchronizeFromDraftCreatesNewLineItemWhenDraftSourceIsNull(): void
    {
        $lineItemDraft = new OrderLineItem();
        $lineItemDraft->setDraftSource(null);

        $orderDraft = new OrderStub();
        ReflectionUtil::setId($orderDraft, 1600);
        $orderDraft->addLineItem($lineItemDraft);

        $order = new OrderStub();

        $this->entityDraftSynchronizer->expects(self::once())
            ->method('synchronizeFromDraft')
            ->with($lineItemDraft, self::isInstanceOf(OrderLineItem::class));

        self::assertCount(0, $order->getLineItems());

        $this->synchronizer->synchronizeFromDraft($orderDraft, $order);

        self::assertCount(1, $order->getLineItems());

        $newLineItem = $order->getLineItems()->first();
        self::assertNotSame($lineItemDraft, $newLineItem);
        self::assertTrue($newLineItem->getDrafts()->contains($lineItemDraft));
    }

    public function testSynchronizeFromDraftRemovesLineItemWhenMarkedForDeletion(): void
    {
        $existingLineItem = new OrderLineItem();
        ReflectionUtil::setId($existingLineItem, 200);

        $lineItemDraft = new OrderLineItem();
        ReflectionUtil::setId($lineItemDraft, 100);
        $lineItemDraft->setDraftSource($existingLineItem);
        $lineItemDraft->setDraftDelete(true);

        $orderDraft = new OrderStub();
        ReflectionUtil::setId($orderDraft, 1700);
        $orderDraft->addLineItem($lineItemDraft);

        $order = new OrderStub();
        $order->addLineItem($existingLineItem);

        $this->entityDraftSynchronizer->expects(self::never())
            ->method('synchronizeFromDraft');

        self::assertCount(1, $order->getLineItems());

        $this->synchronizer->synchronizeFromDraft($orderDraft, $order);

        self::assertCount(0, $order->getLineItems());
        self::assertFalse($order->getLineItems()->contains($existingLineItem));
    }

    public function testSynchronizeFromDraftSynchronizesExistingLineItem(): void
    {
        $existingLineItem = new OrderLineItem();
        ReflectionUtil::setId($existingLineItem, 300);

        $lineItemDraft = new OrderLineItem();
        ReflectionUtil::setId($lineItemDraft, 100);
        $lineItemDraft->setDraftSource($existingLineItem);

        $orderDraft = new OrderStub();
        ReflectionUtil::setId($orderDraft, 1800);
        $orderDraft->addLineItem($lineItemDraft);

        $order = new OrderStub();
        $order->addLineItem($existingLineItem);

        $this->entityDraftSynchronizer->expects(self::once())
            ->method('synchronizeFromDraft')
            ->with($lineItemDraft, $existingLineItem);

        $this->synchronizer->synchronizeFromDraft($orderDraft, $order);

        self::assertCount(1, $order->getLineItems());
        self::assertTrue($order->getLineItems()->contains($existingLineItem));
    }

    public function testSynchronizeToDraftDoesNotAddToDraftCollection(): void
    {
        $order = new OrderStub();
        ReflectionUtil::setId($order, 500);

        $orderDraft = new OrderStub();
        ReflectionUtil::setId($orderDraft, 2000);
        $orderDraft->setDraftSessionUuid('uuid-1234');

        self::assertCount(0, $order->getDrafts());

        $this->synchronizer->synchronizeToDraft($order, $orderDraft);

        self::assertCount(0, $order->getDrafts());
    }

    public function testSynchronizeToDraftCopiesFields(): void
    {
        $organization = new Organization();
        $owner = new User();
        $customer = new Customer();
        $customerUser = new CustomerUser();
        $website = new Website();
        $shipUntil = new \DateTime('2026-08-20');

        $order = new OrderStub();
        ReflectionUtil::setId($order, 500);
        $order->setOrganization($organization);
        $order->setOwner($owner);
        $order->setCustomer($customer);
        $order->setCustomerUser($customerUser);
        $order->setCurrency('USD');
        $order->setWebsite($website);
        $order->setShippingMethod('ups');
        $order->setShippingMethodType('express');
        $order->setEstimatedShippingCostAmount(20.00);
        $order->setOverriddenShippingCostAmount(18.00);
        $order->setPoNumber('PO-2026-999');
        $order->setShipUntil($shipUntil);
        $order->setCustomerNotes('Handle with care');

        $orderDraft = new OrderStub();
        ReflectionUtil::setId($orderDraft, 2000);
        $orderDraft->setDraftSessionUuid('uuid-5678');

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);
        $this->entityManager->expects(self::any())
            ->method('contains')
            ->willReturn(true);

        $this->synchronizer->synchronizeToDraft($order, $orderDraft);

        self::assertSame($organization, $orderDraft->getOrganization());
        self::assertSame($owner, $orderDraft->getOwner());
        self::assertSame($customer, $orderDraft->getCustomer());
        self::assertSame($customerUser, $orderDraft->getCustomerUser());
        self::assertEquals('USD', $orderDraft->getCurrency());
        self::assertSame($website, $orderDraft->getWebsite());
        self::assertEquals('ups', $orderDraft->getShippingMethod());
        self::assertEquals('express', $orderDraft->getShippingMethodType());
        self::assertEquals(20.00, $orderDraft->getEstimatedShippingCostAmount());
        self::assertEquals(18.00, $orderDraft->getOverriddenShippingCostAmount());
        self::assertEquals('PO-2026-999', $orderDraft->getPoNumber());
        self::assertNotNull($orderDraft->getShipUntil());
        self::assertEquals($shipUntil, $orderDraft->getShipUntil());
        self::assertEquals('Handle with care', $orderDraft->getCustomerNotes());
    }
}
