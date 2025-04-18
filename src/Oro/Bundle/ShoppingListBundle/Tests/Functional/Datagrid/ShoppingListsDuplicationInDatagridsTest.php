<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Datagrid;

use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListTotals;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ShoppingListsDuplicationInDatagridsTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([LoadShoppingListTotals::class]);
    }

    public function testShoppingListGrid()
    {
        $response = $this->client->requestGrid(
            [
                'gridName' => 'shopping-list-grid',
                'shopping-list-grid[_pager][_per_page]' => 20,
            ],
            [],
            true
        );
        $result = static::getJsonResponseContent($response, 200);

        static::assertCount(12, $result['data']);
        $this->assertNoDuplicatedShoppingLists($result['data']);
        foreach ($result['data'] as $data) {
            static::assertNotEquals('$1,000.00', $data['subtotal']);
        }
    }

    public function testCustomerShoppingListsGrid()
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $customer = $shoppingList->getCustomer();
        $response = $this->client->requestGrid(
            [
                'gridName' => 'customer-shopping-lists-grid',
                'customer-shopping-lists-grid[customer_id]' => $customer->getId(),
                'customer-shopping-lists-grid[_pager][_per_page]' => 20,
            ],
            [],
            true
        );
        $result = static::getJsonResponseContent($response, 200);

        static::assertCount(7, $result['data']);
        $this->assertNoDuplicatedShoppingLists($result['data']);
        foreach ($result['data'] as $data) {
            static::assertNotEquals('$1,000.00', $data['subtotal']);
        }
    }

    public function testCustomerUserShoppingListsGrid()
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_7);
        $customerUser = $shoppingList->getCustomerUser();
        $response = $this->client->requestGrid(
            [
                'gridName' => 'customer-user-shopping-lists-grid',
                'customer-user-shopping-lists-grid[customer_user_id]' => $customerUser->getId(),
                'customer-user-shopping-lists-grid[_pager][_per_page]' => 20,
            ],
            [],
            true
        );
        $result = static::getJsonResponseContent($response, 200);

        static::assertCount(1, $result['data']);
        foreach ($result['data'] as $data) {
            static::assertNotEquals('$1,000.00', $data['subtotal']);
        }
    }

    private function assertNoDuplicatedShoppingLists(array $data): void
    {
        $values = [];

        foreach ($data as $item) {
            static::assertNotContains(
                $item['id'],
                $values,
                sprintf('Duplication for ShoppingList with id "%s".', $item['id'])
            );
            $values[] = $item['id'];
        }
    }
}
