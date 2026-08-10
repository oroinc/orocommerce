<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Operation;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Operation\CreateOrderDraftFromShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\DraftSession\Manager\DraftSessionOrmFilterManager;

/**
 * @dbIsolationPerTest
 */
final class CreateOrderDraftFromShoppingListTest extends WebTestCase
{
    private CreateOrderDraftFromShoppingList $operation;
    private EntityManagerInterface $entityManager;
    private DraftSessionOrmFilterManager $draftSessionOrmFilterManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([
            LoadProductData::class,
            LoadProductUnits::class,
            LoadShoppingLists::class,
            LoadShoppingListLineItems::class,
        ]);

        $this->operation = self::getContainer()
            ->get('oro_shopping_list.operation.create_order_draft_from_shopping_list');

        $this->entityManager = self::getContainer()
            ->get('doctrine')
            ->getManagerForClass(Order::class);

        // Order drafts are hidden by the draft-session ORM filter, so it must be disabled
        // to be able to query the persisted draft directly from the database.
        $this->draftSessionOrmFilterManager = self::getContainer()
            ->get('oro_order.draft_session.manager.draft_session_orm_filter_manager');
        $this->draftSessionOrmFilterManager->disable();
    }

    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->draftSessionOrmFilterManager->enable();
    }

    public function testCreatesOrderDraftWithMappedFieldsAndLineItems(): void
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        /** @var LineItem $lineItem */
        $lineItem = $this->getReference(LoadShoppingListLineItems::LINE_ITEM_1);

        $orderDraft = $this->operation->createOrderDraftFromShoppingList($shoppingList);

        self::assertInstanceOf(Order::class, $orderDraft);
        self::assertNotEmpty($orderDraft->getDraftSessionUuid());

        // Mapped scalar fields.
        self::assertSame($shoppingList->getCurrency(), $orderDraft->getCurrency());
        self::assertSame($shoppingList->getNotes(), $orderDraft->getCustomerNotes());

        // Mapped relations.
        self::assertNotNull($orderDraft->getCustomer());
        self::assertSame($shoppingList->getCustomer()->getId(), $orderDraft->getCustomer()->getId());
        self::assertNotNull($orderDraft->getCustomerUser());
        self::assertSame($shoppingList->getCustomerUser()->getId(), $orderDraft->getCustomerUser()->getId());
        self::assertNotNull($orderDraft->getWebsite());
        self::assertSame($shoppingList->getWebsite()->getId(), $orderDraft->getWebsite()->getId());
        self::assertNotNull($orderDraft->getOrganization());
        self::assertSame($shoppingList->getOrganization()->getId(), $orderDraft->getOrganization()->getId());

        // Source entity back-reference.
        self::assertSame(ShoppingList::class, $orderDraft->getSourceEntityClass());
        self::assertEquals($shoppingList->getId(), $orderDraft->getSourceEntityId());
        self::assertEquals($shoppingList->getIdentifier(), $orderDraft->getSourceEntityIdentifier());

        // Line items are synchronized from the shopping list.
        self::assertCount(1, $orderDraft->getLineItems());
        /** @var OrderLineItem $orderLineItem */
        $orderLineItem = $orderDraft->getLineItems()->first();
        self::assertSame($lineItem->getProductSku(), $orderLineItem->getProductSku());
        self::assertSame($orderDraft->getDraftSessionUuid(), $orderLineItem->getDraftSessionUuid());

        // The draft is persisted and can be loaded back from the database.
        $this->entityManager->clear();
        $persistedDraft = $this->entityManager->getRepository(Order::class)->findOneBy([
            'draftSessionUuid' => $orderDraft->getDraftSessionUuid(),
        ]);
        self::assertNotNull($persistedDraft, 'Order draft should be persisted in the draft session');
        self::assertSame($orderDraft->getId(), $persistedDraft->getId());
        self::assertCount(1, $persistedDraft->getLineItems());
    }

    public function testEachInvocationCreatesADistinctDraftSession(): void
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);

        $firstDraft = $this->operation->createOrderDraftFromShoppingList($shoppingList);
        $secondDraft = $this->operation->createOrderDraftFromShoppingList($shoppingList);

        self::assertNotEmpty($firstDraft->getDraftSessionUuid());
        self::assertNotEmpty($secondDraft->getDraftSessionUuid());
        self::assertNotSame(
            $firstDraft->getDraftSessionUuid(),
            $secondDraft->getDraftSessionUuid(),
            'Each operation invocation must generate its own draft session UUID'
        );
        self::assertNotSame($firstDraft->getId(), $secondDraft->getId());
    }

    public function testCreatesOrderDraftFromEmptyShoppingList(): void
    {
        // SHOPPING_LIST_2 has no line items in the loaded fixtures.
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_2);
        self::assertCount(0, $shoppingList->getLineItems());

        $orderDraft = $this->operation->createOrderDraftFromShoppingList($shoppingList);

        self::assertInstanceOf(Order::class, $orderDraft);
        self::assertNotEmpty($orderDraft->getDraftSessionUuid());
        self::assertCount(0, $orderDraft->getLineItems());

        $this->entityManager->clear();
        $persistedDraft = $this->entityManager->getRepository(Order::class)->findOneBy([
            'draftSessionUuid' => $orderDraft->getDraftSessionUuid(),
        ]);
        self::assertNotNull($persistedDraft, 'Order draft should be persisted even for an empty shopping list');
        self::assertEquals($shoppingList->getId(), $persistedDraft->getSourceEntityId());
        self::assertCount(0, $persistedDraft->getLineItems());
    }
}
