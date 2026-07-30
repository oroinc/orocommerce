<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\ControllerFrontend;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Security\AnonymousCustomerUserAuthenticator;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerVisitors;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadGuestShoppingListLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadGuestShoppingLists;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;

/**
 * @dbIsolationPerTest
 */
final class AjaxLineItemBatchUpdateGuestControllerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private const string GRID_NAME = 'frontend-customer-user-shopping-list-edit-grid';

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadShoppingListLineItems::class,
            LoadGuestShoppingListLineItems::class,
        ]);

        $configManager = self::getConfigManager();
        $configManager->set('oro_shopping_list.availability_for_guests', true);
        $configManager->flush();

        $this->operateAsCustomerVisitor();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_shopping_list.availability_for_guests', false);
        $configManager->flush();
        $configManager->reload();

        self::getContainer()->get('security.token_storage')->setToken(null);
        self::getContainer()->get(FrontendHelper::class)->resetRequestEmulation();

        parent::tearDown();
    }

    public function testGuestCanUpdateOwnGuestShoppingListLineItem(): void
    {
        // Sanity check: a guest is still able to update line items of its own guest shopping list.
        /** @var ShoppingList $guestShoppingList */
        $guestShoppingList = $this->getReference(LoadGuestShoppingLists::GUEST_SHOPPING_LIST_1);
        /** @var LineItem $guestLineItem */
        $guestLineItem = $this->getReference(LoadGuestShoppingListLineItems::LINE_ITEM_1);

        $guestLineItemId = $guestLineItem->getId();

        $this->sendBatchUpdateRequest(
            $guestShoppingList,
            [['id' => $guestLineItemId, 'quantity' => 8, 'unitCode' => $guestLineItem->getUnit()->getCode()]]
        );

        self::assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

        self::assertEqualsWithDelta(8, $this->getLineItemQuantity($guestLineItemId), 1e-6);
    }

    public function testGuestCannotUpdateLineItemOfAnotherCustomerViaOwnGuestShoppingList(): void
    {
        // A guest references a registered customer's line item while updating its own guest shopping list.
        /** @var ShoppingList $guestShoppingList */
        $guestShoppingList = $this->getReference(LoadGuestShoppingLists::GUEST_SHOPPING_LIST_1);
        /** @var ShoppingList $customerShoppingList */
        $customerShoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        /** @var LineItem $customerLineItem */
        $customerLineItem = $this->getReference(LoadShoppingListLineItems::LINE_ITEM_1);

        $customerLineItemId = $customerLineItem->getId();
        $expectedCustomerUserId = $customerShoppingList->getCustomerUser()->getId();
        $expectedShoppingListId = $customerShoppingList->getId();
        $expectedQuantity = (float)$customerLineItem->getQuantity();

        $this->sendBatchUpdateRequest(
            $guestShoppingList,
            [['id' => $customerLineItemId, 'quantity' => 99, 'unitCode' => $customerLineItem->getUnit()->getCode()]]
        );

        self::assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

        // The registered customer's line item is neither updated nor re-assigned to the guest shopping list.
        self::assertEqualsWithDelta($expectedQuantity, $this->getLineItemQuantity($customerLineItemId), 1e-6);
        self::assertSame($expectedCustomerUserId, $this->getLineItemCustomerUserId($customerLineItemId));
        self::assertSame($expectedShoppingListId, $this->getLineItemShoppingListId($customerLineItemId));
    }

    public function testGuestCannotUpdateLineItemOfAnotherCustomerViaTheirShoppingList(): void
    {
        // A guest targets a registered customer's shopping list directly.
        /** @var ShoppingList $customerShoppingList */
        $customerShoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        /** @var LineItem $customerLineItem */
        $customerLineItem = $this->getReference(LoadShoppingListLineItems::LINE_ITEM_1);

        $customerLineItemId = $customerLineItem->getId();
        $expectedCustomerUserId = $customerShoppingList->getCustomerUser()->getId();
        $expectedShoppingListId = $customerShoppingList->getId();
        $expectedQuantity = (float)$customerLineItem->getQuantity();

        $this->sendBatchUpdateRequest(
            $customerShoppingList,
            [['id' => $customerLineItemId, 'quantity' => 99, 'unitCode' => $customerLineItem->getUnit()->getCode()]]
        );

        // A guest is not allowed to access a registered customer's shopping list grid.
        self::assertContains(
            $this->client->getResponse()->getStatusCode(),
            [401, 403]
        );

        // The registered customer's line item is left intact.
        self::assertEqualsWithDelta($expectedQuantity, $this->getLineItemQuantity($customerLineItemId), 1e-6);
        self::assertSame($expectedCustomerUserId, $this->getLineItemCustomerUserId($customerLineItemId));
        self::assertSame($expectedShoppingListId, $this->getLineItemShoppingListId($customerLineItemId));
    }

    private function operateAsCustomerVisitor(): void
    {
        $visitor = $this->getReference(LoadCustomerVisitors::CUSTOMER_VISITOR);
        $this->client->getCookieJar()->set(
            new Cookie(
                AnonymousCustomerUserAuthenticator::COOKIE_NAME,
                base64_encode(json_encode($visitor->getSessionId(), JSON_THROW_ON_ERROR)),
                time() + 60
            )
        );
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
