<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\ControllerFrontend;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
final class AjaxLineItemBatchUpdateControllerTest extends WebTestCase
{
    private const string GRID_NAME = 'frontend-customer-user-shopping-list-edit-grid';

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->loadFixtures([LoadShoppingListLineItems::class]);
    }

    public function testBatchUpdateUpdatesOwnLineItems(): void
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        /** @var LineItem $lineItem */
        $lineItem = $this->getReference(LoadShoppingListLineItems::LINE_ITEM_1);

        $lineItemId = $lineItem->getId();

        $this->sendBatchUpdateRequest(
            $shoppingList,
            [
                ['id' => $lineItemId, 'quantity' => 5, 'unitCode' => $lineItem->getUnit()->getCode()],
            ]
        );

        self::assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

        self::assertEqualsWithDelta(5, $this->getLineItemQuantity($lineItemId), 1e-6);
    }

    public function testBatchUpdateSkipsLineItemOfAnotherCustomerUser(): void
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        /** @var LineItem $ownLineItem */
        $ownLineItem = $this->getReference(LoadShoppingListLineItems::LINE_ITEM_1);
        // LINE_ITEM_9 belongs to a shopping list of another customer user, so it must not be editable.
        /** @var LineItem $foreignLineItem */
        $foreignLineItem = $this->getReference(LoadShoppingListLineItems::LINE_ITEM_9);

        $ownLineItemId = $ownLineItem->getId();
        $foreignLineItemId = $foreignLineItem->getId();
        $foreignQuantity = (float)$foreignLineItem->getQuantity();

        $this->sendBatchUpdateRequest(
            $shoppingList,
            [
                ['id' => $ownLineItemId, 'quantity' => 9, 'unitCode' => $ownLineItem->getUnit()->getCode()],
                [
                    'id' => $foreignLineItemId,
                    'quantity' => 99,
                    'unitCode' => $foreignLineItem->getUnit()->getCode(),
                ],
            ]
        );

        self::assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

        // The line item owned by the current customer user is updated.
        self::assertEqualsWithDelta(9, $this->getLineItemQuantity($ownLineItemId), 1e-6);
        // The line item of another customer user is skipped and its quantity is left intact.
        self::assertEqualsWithDelta($foreignQuantity, $this->getLineItemQuantity($foreignLineItemId), 1e-6);
    }

    public function testBatchUpdateDoesNotReassignLineItemOfAnotherCustomer(): void
    {
        // The route is the current user's own shopping list, but the request references a line item
        // that belongs to another customer user.
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        /** @var ShoppingList $foreignShoppingList */
        $foreignShoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_6);
        /** @var LineItem $foreignLineItem */
        $foreignLineItem = $this->getReference(LoadShoppingListLineItems::LINE_ITEM_9);

        $foreignLineItemId = $foreignLineItem->getId();
        $expectedCustomerUserId = $foreignShoppingList->getCustomerUser()->getId();
        $expectedShoppingListId = $foreignShoppingList->getId();
        $expectedQuantity = (float)$foreignLineItem->getQuantity();

        $this->sendBatchUpdateRequest(
            $shoppingList,
            [['id' => $foreignLineItemId, 'quantity' => 99, 'unitCode' => $foreignLineItem->getUnit()->getCode()]]
        );

        self::assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

        // The line item is neither updated nor re-assigned to the current customer user / shopping list.
        self::assertEqualsWithDelta($expectedQuantity, $this->getLineItemQuantity($foreignLineItemId), 1e-6);
        self::assertSame($expectedCustomerUserId, $this->getLineItemCustomerUserId($foreignLineItemId));
        self::assertSame($expectedShoppingListId, $this->getLineItemShoppingListId($foreignLineItemId));
    }

    public function testBatchUpdateCannotMoveLineItemIntoAnotherCustomerShoppingList(): void
    {
        // The current user owns the line item but targets another customer user's shopping list as the route.
        // The grid access check denies the request, and, more importantly, the line item must not be moved
        // to (and thus re-owned by) the targeted shopping list.
        /** @var ShoppingList $foreignShoppingList */
        $foreignShoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_6);
        /** @var ShoppingList $ownShoppingList */
        $ownShoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        /** @var LineItem $ownLineItem */
        $ownLineItem = $this->getReference(LoadShoppingListLineItems::LINE_ITEM_1);

        $ownLineItemId = $ownLineItem->getId();
        $expectedCustomerUserId = $ownShoppingList->getCustomerUser()->getId();
        $expectedShoppingListId = $ownShoppingList->getId();
        $expectedQuantity = (float)$ownLineItem->getQuantity();

        $this->sendBatchUpdateRequest(
            $foreignShoppingList,
            [['id' => $ownLineItemId, 'quantity' => 99, 'unitCode' => $ownLineItem->getUnit()->getCode()]]
        );

        // Access to another customer user's shopping list grid is forbidden.
        self::assertResponseStatusCodeEquals($this->client->getResponse(), 403);

        // The line item stays with its original customer user and shopping list.
        self::assertSame($expectedCustomerUserId, $this->getLineItemCustomerUserId($ownLineItemId));
        self::assertSame($expectedShoppingListId, $this->getLineItemShoppingListId($ownLineItemId));
        self::assertEqualsWithDelta($expectedQuantity, $this->getLineItemQuantity($ownLineItemId), 1e-6);
    }

    public function testBatchUpdateDoesNotMoveLineItemToAnotherShoppingList(): void
    {
        // The line item is only referenced by id in the request, so a line item that belongs to a different
        // shopping list must not be pulled into (and re-assigned to) the shopping list from the route,
        // even when both shopping lists belong to the current customer user.
        /** @var ShoppingList $otherShoppingList */
        $otherShoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_2);
        /** @var ShoppingList $ownShoppingList */
        $ownShoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        /** @var LineItem $lineItem */
        $lineItem = $this->getReference(LoadShoppingListLineItems::LINE_ITEM_1);

        $lineItemId = $lineItem->getId();
        $expectedShoppingListId = $ownShoppingList->getId();
        $expectedQuantity = (float)$lineItem->getQuantity();

        $this->sendBatchUpdateRequest(
            $otherShoppingList,
            [['id' => $lineItemId, 'quantity' => 99, 'unitCode' => $lineItem->getUnit()->getCode()]]
        );

        self::assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

        // The line item is not moved to the shopping list from the route and its quantity is left intact.
        self::assertSame($expectedShoppingListId, $this->getLineItemShoppingListId($lineItemId));
        self::assertEqualsWithDelta($expectedQuantity, $this->getLineItemQuantity($lineItemId), 1e-6);
    }

    private function sendBatchUpdateRequest(ShoppingList $shoppingList, array $data): void
    {
        $this->ajaxRequest(
            'PUT',
            $this->getUrl('oro_shopping_list_frontend_line_item_batch_update', ['id' => $shoppingList->getId()]),
            [],
            [],
            [],
            json_encode(
                [
                    'data' => $data,
                    'gridName' => self::GRID_NAME,
                    'fetchData' => [self::GRID_NAME => ['shopping_list_id' => $shoppingList->getId()]],
                ],
                JSON_THROW_ON_ERROR
            )
        );
    }

    private function getLineItemQuantity(int $lineItemId): float
    {
        return (float)$this->fetchLineItemColumn('quantity', $lineItemId);
    }

    private function getLineItemCustomerUserId(int $lineItemId): int
    {
        return (int)$this->fetchLineItemColumn('customer_user_id', $lineItemId);
    }

    private function getLineItemShoppingListId(int $lineItemId): int
    {
        return (int)$this->fetchLineItemColumn('shopping_list_id', $lineItemId);
    }

    private function fetchLineItemColumn(string $column, int $lineItemId): mixed
    {
        $connection = self::getContainer()->get('doctrine')->getConnection();

        return $connection->fetchOne(
            sprintf('SELECT %s FROM oro_shopping_list_line_item WHERE id = ?', $column),
            [$lineItemId]
        );
    }
}
