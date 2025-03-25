<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\ApiFrontendForVisitor\RestJsonApi;

use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class ShoppingListForVisitorWithoutShoppingListTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeVisitor();
        $this->loadFixtures([
            '@OroShoppingListBundle/Tests/Functional/ApiFrontendForVisitor/DataFixtures/shopping_list_for_visitor.yml'
        ]);

        /** @var ShoppingListTotalManager $totalManager */
        $totalManager = self::getContainer()->get('oro_shopping_list.manager.shopping_list_total');
        for ($i = 1; $i <= 3; $i++) {
            $totalManager->recalculateTotals(
                $this->getReference(sprintf('shopping_list%d', $i)),
                true
            );
        }

        // guard
        self::assertFalse(self::getConfigManager()->get('oro_shopping_list.availability_for_guests'));
    }

    public function testTryToGetList(): void
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->cget(
            ['entity' => 'shoppinglists'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToGetListForNewVisitor(): void
    {
        $response = $this->cget(
            ['entity' => 'shoppinglists'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToAddToCart(): void
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $shoppingListId = $this->getReference('shopping_list1')->getId();

        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => (string)$shoppingListId, 'association' => 'items'],
            'add_line_item_visitor.yml',
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToAddToCartForDefaultShoppingListForNewVisitor(): void
    {
        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => 'default', 'association' => 'items'],
            'add_line_item_visitor.yml',
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToGet(): void
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->get(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToGetForDefaultShoppingList(): void
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->get(
            ['entity' => 'shoppinglists', 'id' => 'default'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToGetForDefaultShoppingListForNewVisitor(): void
    {
        $response = $this->get(
            ['entity' => 'shoppinglists', 'id' => 'default'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testGetLineItemsFilteredByDefaultShoppingListForNewVisitor(): void
    {
        $response = $this->cget(
            ['entity' => 'shoppinglistitems'],
            ['filter' => ['shoppingList' => 'default']],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToGetNotVisitorShoppingList(): void
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->get(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list2->id)>'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToUpdate(): void
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $shoppingListId = $this->getReference('shopping_list1')->getId();
        $data = [
            'data' => [
                'type'       => 'shoppinglists',
                'id'         => (string)$shoppingListId,
                'attributes' => [
                    'name' => 'Updated Shopping List'
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'shoppinglists', 'id' => (string)$shoppingListId],
            $data,
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToUpdateNotVisitorShoppingList(): void
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $shoppingListId = $this->getReference('shopping_list2')->getId();
        $data = [
            'data' => [
                'type'       => 'shoppinglists',
                'id'         => (string)$shoppingListId,
                'attributes' => [
                    'name' => 'Updated Shopping List'
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'shoppinglists', 'id' => (string)$shoppingListId],
            $data,
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToDeleteWithDefaultPermissions(): void
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $shoppingListId = $this->getReference('shopping_list1')->getId();

        $response = $this->delete(
            ['entity' => 'shoppinglists', 'id' => (string)$shoppingListId],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToCreateWithDefaultPermissions(): void
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->post(
            ['entity' => 'shoppinglists'],
            '../../ApiFrontend/RestJsonApi/requests/create_shopping_list.yml',
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToGetListWhenVisitorHasNoAccessToEditShoppingList(): void
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->cget(
            ['entity' => 'shoppinglists'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToGetWhenGuestShoppingListFeatureIsDisabled(): void
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->get(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToAddToCartWhenGuestShoppingListFeatureIsDisabled(): void
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $shoppingListId = $this->getReference('shopping_list1')->getId();

        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => (string)$shoppingListId, 'association' => 'items'],
            'add_line_item_visitor.yml',
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToUpdateWhenGuestShoppingListFeatureIsDisabled(): void
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $shoppingListId = $this->getReference('shopping_list1')->getId();
        $data = [
            'data' => [
                'type'       => 'shoppinglists',
                'id'         => (string)$shoppingListId,
                'attributes' => [
                    'name' => 'Updated Shopping List'
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'shoppinglists', 'id' => (string)$shoppingListId],
            $data,
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testOptionsWhenGuestShoppingListFeatureIsDisabled(): void
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->options(
            $this->getListRouteName(),
            ['entity' => 'shoppinglists']
        );

        self::assertAllowResponseHeader($response, 'OPTIONS, GET, POST, DELETE');
    }
}
