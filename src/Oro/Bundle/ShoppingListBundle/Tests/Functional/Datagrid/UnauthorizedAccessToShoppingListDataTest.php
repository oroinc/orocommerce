<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Datagrid;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Security\Firewall\AnonymousCustomerUserAuthenticationListener;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerVisitors;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadGuestShoppingListLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadGuestShoppingLists;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class UnauthorizedAccessToShoppingListDataTest extends WebTestCase
{
    private bool $guestShoppingListAccess;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadShoppingListLineItems::class,
            LoadGuestShoppingListLineItems::class
        ]);

        /** @var ConfigManager $configManager */
        $configManager = self::getContainer()->get('oro_config.manager');
        $this->guestShoppingListAccess = $configManager->get('oro_shopping_list.availability_for_guests');
        $configManager->set('oro_shopping_list.availability_for_guests', true);
        $configManager->flush();
    }

    #[\Override]
    protected function tearDown(): void
    {
        /** @var ConfigManager $configManager */
        $configManager = self::getContainer()->get('oro_config.manager');
        $configManager->set('oro_shopping_list.availability_for_guests', $this->guestShoppingListAccess);
        $configManager->flush();

        self::getContainer()->get('security.token_storage')->setToken(null);
        self::getContainer()->get(FrontendHelper::class)->resetRequestEmulation();

        parent::tearDown();
    }

    /**
     * @dataProvider protectedShoppingListGridsDataProvider
     */
    public function testCustomerUserAccessToShoppingListGrid(string $gridName)
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $response = $this->client->requestFrontendGrid(
            [
                'gridName' => $gridName,
                $gridName . '[_pager][_per_page]' => 1000,
            ],
            [],
            true
        );
        $result = static::getJsonResponseContent($response, 200);

        static::assertEquals(6, $result['options']['totalRecords']);

        $actualLabels = array_map(static fn (array $row) => $row['label'], $result['data']);
        $expectedLabels = [
            $this->getReference(LoadShoppingLists::SHOPPING_LIST_1)->getLabel(),
            $this->getReference(LoadShoppingLists::SHOPPING_LIST_2)->getLabel(),
            $this->getReference(LoadShoppingLists::SHOPPING_LIST_3)->getLabel(),
            $this->getReference(LoadShoppingLists::SHOPPING_LIST_4)->getLabel(),
            $this->getReference(LoadShoppingLists::SHOPPING_LIST_5)->getLabel(),
            $this->getReference(LoadShoppingLists::SHOPPING_LIST_8)->getLabel(),
        ];
        static::assertEqualsCanonicalizing($expectedLabels, $actualLabels);
    }

    /**
     * @dataProvider protectedShoppingListGridsDataProvider
     */
    public function testAnonymousAccessToShoppingListGrid(string $gridName)
    {
        $response = $this->client->requestFrontendGrid(
            [
                'gridName' => $gridName,
                $gridName . '[_pager][_per_page]' => 1000,
            ],
            [],
            true
        );
        self::assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function protectedShoppingListGridsDataProvider(): array
    {
        return [
            ['frontend-customer-user-shopping-lists-base-grid'],
            ['frontend-customer-user-shopping-list-select-grid'],
            ['frontend-customer-user-shopping-lists-grid']
        ];
    }

    /**
     * @dataProvider protectedLineItemsGridsDataProvider
     */
    public function testAnonymousAccessToAllowedShoppingListLineItemsGrid(string $gridName)
    {
        $this->operateAsCustomerVisitor();

        $shoppingListId = $this->getReference(LoadGuestShoppingLists::GUEST_SHOPPING_LIST_1)->getId();
        $response = $this->client->requestFrontendGrid(
            [
                'gridName' => $gridName,
                $gridName . '[shopping_list_id]' => $shoppingListId
            ],
            [],
            true
        );

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @dataProvider protectedLineItemsGridsDataProvider
     */
    public function testAnonymousAccessToDisallowedShoppingListLineItemsGrid(string $gridName)
    {
        $this->operateAsCustomerVisitor();

        $shoppingListId = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1)->getId();
        $response = $this->client->requestFrontendGrid(
            [
                'gridName' => $gridName,
                $gridName . '[shopping_list_id]' => $shoppingListId
            ],
            [],
            true
        );

        self::assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    /**
     * @dataProvider protectedLineItemsGridsDataProvider
     */
    public function testShoppingListLineItemsDatagridWithCustomerUser(string $gridName): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $shoppingListId = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1)->getId();
        $gridResponse = $this->client->requestFrontendGrid(
            [
                'gridName' => $gridName,
                $gridName . '[shopping_list_id]' => $shoppingListId
            ],
            [],
            true
        );
        self::assertEquals(Response::HTTP_OK, $gridResponse->getStatusCode());
    }

    /**
     * @dataProvider protectedLineItemsGridsDataProvider
     */
    public function testGuestShoppingListLineItemsDatagridWithCustomerUser(string $gridName): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $shoppingListId = $this->getReference(LoadGuestShoppingLists::GUEST_SHOPPING_LIST_1)->getId();
        $gridResponse = $this->client->requestFrontendGrid(
            [
                'gridName' => $gridName,
                $gridName . '[shopping_list_id]' => $shoppingListId
            ],
            [],
            true
        );
        self::assertEquals(Response::HTTP_FORBIDDEN, $gridResponse->getStatusCode());
    }

    public function protectedLineItemsGridsDataProvider(): array
    {
        return [
            ['frontend-customer-user-shopping-list-grid'],
            ['frontend-customer-user-shopping-list-edit-grid']
        ];
    }

    private function operateAsCustomerVisitor(): void
    {
        $visitor = $this->getReference(LoadCustomerVisitors::CUSTOMER_VISITOR);
        $this->client->getCookieJar()->set(
            new Cookie(
                AnonymousCustomerUserAuthenticationListener::COOKIE_NAME,
                base64_encode(\json_encode([$visitor->getId(), $visitor->getSessionId()])),
                time() + 60
            )
        );
    }
}
