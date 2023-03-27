<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class ShoppingListForVisitorWithShoppingListTest extends FrontendRestJsonApiTestCase
{
    use RolePermissionExtension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableVisitor();
        $this->loadFixtures([
            '@OroShoppingListBundle/Tests/Functional/Api/Frontend/DataFixtures/shopping_list_for_visitor.yml'
        ]);

        /** @var ShoppingListTotalManager $totalManager */
        $totalManager = self::getContainer()->get('oro_shopping_list.manager.shopping_list_total');
        for ($i = 1; $i <= 3; $i++) {
            $totalManager->recalculateTotals(
                $this->getReference(sprintf('shopping_list%d', $i)),
                true
            );
        }

        $configManager = $this->getConfigManager();
        $configManager->set('oro_shopping_list.availability_for_guests', true);
        $configManager->flush();
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    private static function assertLineItem(
        LineItem $lineItem,
        int $organizationId,
        int $shoppingListId,
        float $quantity,
        string $productUnitCode,
        int $productId,
        string $notes = null,
        int $parentProductId = null
    ) {
        self::assertEquals($organizationId, $lineItem->getOrganization()->getId());
        self::assertTrue(null === $lineItem->getOwner());
        self::assertTrue(null === $lineItem->getCustomerUser());
        self::assertEquals($shoppingListId, $lineItem->getShoppingList()->getId());
        self::assertEquals($quantity, $lineItem->getQuantity());
        self::assertEquals($productUnitCode, $lineItem->getProductUnit()->getCode());
        self::assertEquals($productId, $lineItem->getProduct()->getId());
        if (null === $parentProductId) {
            self::assertTrue(null === $lineItem->getParentProduct());
        } else {
            self::assertEquals($parentProductId, $lineItem->getParentProduct()->getId());
        }
        if (null === $notes) {
            self::assertTrue(null === $lineItem->getNotes());
        } else {
            self::assertEquals($notes, $lineItem->getNotes());
        }
    }

    private function getLineItemById(ShoppingList $shoppingList, int $lineItemId): LineItem
    {
        /** @var LineItem $lineItem */
        $lineItem = null;
        foreach ($shoppingList->getLineItems() as $item) {
            if ($item->getId() === $lineItemId) {
                $lineItem = $item;
                break;
            }
        }
        self::assertNotNull($lineItem);

        return $lineItem;
    }

    public function testGetList()
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->cget(['entity' => 'shoppinglists'], [], ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains('cget_shopping_list_visitor.yml', $response);
        self::assertEquals(1, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGetListForNewVisitor()
    {
        $response = $this->cget(['entity' => 'shoppinglists'], [], ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => []], $response);
        self::assertEquals(0, $response->headers->get('X-Include-Total-Count'));
    }

    public function testAddToCart()
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $organizationId = $this->getReference('organization')->getId();
        $productId = $this->getReference('product2')->getId();
        $productUnitCode = $this->getReference('set')->getCode();
        $shoppingListId = $this->getReference('shopping_list1')->getId();

        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => (string)$shoppingListId, 'association' => 'items'],
            'add_line_item_visitor.yml'
        );

        $responseContent = $this->updateResponseContent('add_line_item_visitor.yml', $response);
        $this->assertResponseContains($responseContent, $response);

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntityManager()
            ->getRepository(ShoppingList::class)
            ->find($shoppingListId);
        self::assertCount(3, $shoppingList->getLineItems());
        $lineItem = $this->getLineItemById($shoppingList, (int)$responseContent['data'][0]['id']);
        self::assertLineItem(
            $lineItem,
            $organizationId,
            $shoppingListId,
            10,
            $productUnitCode,
            $productId,
            'New Line Item Notes'
        );
    }

    public function testAddToCartForNewVisitorForDefaultShoppingList()
    {
        $organizationId = $this->getReference('organization')->getId();
        $productId = $this->getReference('product2')->getId();
        $productUnitCode = $this->getReference('set')->getCode();

        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => 'default', 'association' => 'items'],
            'add_line_item_visitor.yml'
        );

        $responseContent = $this->updateResponseContent('add_line_item_visitor.yml', $response);
        $this->assertResponseContains($responseContent, $response);

        /** @var LineItem $lineItem */
        $lineItem = $this->getEntityManager()
            ->getRepository(LineItem::class)
            ->find((int)$responseContent['data'][0]['id']);
        $shoppingList = $lineItem->getShoppingList();
        self::assertEquals(
            self::getContainer()->get('translator')->trans('oro.shoppinglist.default.label'),
            $shoppingList->getLabel()
        );
        self::assertCount(1, $shoppingList->getLineItems());
        self::assertLineItem(
            $lineItem,
            $organizationId,
            $shoppingList->getId(),
            10,
            $productUnitCode,
            $productId,
            'New Line Item Notes'
        );
    }

    public function testAddToCartForDefaultShoppingList()
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $organizationId = $this->getReference('organization')->getId();
        $productId = $this->getReference('product2')->getId();
        $productUnitCode = $this->getReference('set')->getCode();
        $shoppingListId = $this->getReference('shopping_list1')->getId();

        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => 'default', 'association' => 'items'],
            'add_line_item_visitor.yml'
        );

        $responseContent = $this->updateResponseContent('add_line_item_visitor.yml', $response);
        $this->assertResponseContains($responseContent, $response);

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntityManager()
            ->getRepository(ShoppingList::class)
            ->find($shoppingListId);
        self::assertCount(3, $shoppingList->getLineItems());
        $lineItem = $this->getLineItemById($shoppingList, (int)$responseContent['data'][0]['id']);
        self::assertLineItem(
            $lineItem,
            $organizationId,
            $shoppingListId,
            10,
            $productUnitCode,
            $productId,
            'New Line Item Notes'
        );
    }

    public function testGet()
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->get(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>']
        );

        $this->assertResponseContains('get_shopping_list_visitor.yml', $response);
    }

    public function testGetForDefaultShoppingList()
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->get(
            ['entity' => 'shoppinglists', 'id' => 'default']
        );

        $this->assertResponseContains('get_shopping_list_visitor.yml', $response);
    }

    public function testTryToGetForDefaultShoppingListForNewVisitor()
    {
        $response = $this->get(
            ['entity' => 'shoppinglists', 'id' => 'default'],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'not found http exception',
                'detail' => 'An entity with the requested identifier does not exist.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testGetLineItemsFilteredByDefaultShoppingListForNewVisitor()
    {
        $response = $this->cget(
            ['entity' => 'shoppinglistitems'],
            ['filter' => ['shoppingList' => 'default']],
            ['HTTP_X-Include' => 'totalCount']
        );

        $this->assertResponseContains(['data' => []], $response);
        self::assertEquals(0, $response->headers->get('X-Include-Total-Count'));
    }

    public function testTryToCreateShoppingListIfVisitorAlreadyHaveOne()
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->post(
            ['entity' => 'shoppinglists'],
            'create_shopping_list_min.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'create shopping list constraint',
                'detail' => 'It is not allowed to create a new shopping list.'
            ],
            $response
        );
    }

    public function testCreateLineItemForDefaultShoppingListForNewVisitor()
    {
        $organizationId = $this->getReference('organization')->getId();
        $productId = $this->getReference('product2')->getId();
        $productUnitCode = $this->getReference('set')->getCode();

        $data = $this->getRequestData('create_line_item_visitor.yml');
        $data['data']['relationships']['shoppingList']['data']['id'] = 'default';
        $response = $this->post(
            ['entity' => 'shoppinglistitems'],
            $data
        );

        $lineItemId = (int)$this->getResourceId($response);

        /** @var LineItem $lineItem */
        $lineItem = $this->getEntityManager()
            ->getRepository(LineItem::class)
            ->find($lineItemId);
        self::assertNotNull($lineItem);
        $shoppingList = $lineItem->getShoppingList();
        self::assertNotNull($shoppingList);

        $responseContent = $this->updateResponseContent('create_line_item_visitor.yml', $response);
        $responseContent['data']['relationships']['shoppingList']['data']['id'] = (string)$shoppingList->getId();
        $this->assertResponseContains($responseContent, $response);

        self::assertCount(1, $shoppingList->getLineItems());
        self::assertLineItem(
            $lineItem,
            $organizationId,
            $shoppingList->getId(),
            10,
            $productUnitCode,
            $productId,
            'New Line Item Notes'
        );
    }

    public function testUpdate()
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
            $data
        );

        $this->assertResponseContains($data, $response);
    }

    public function testCreateWithLineItemWhenTheRoleHasNoPermissions()
    {
        $this->updateRolePermissions(
            'ROLE_FRONTEND_ANONYMOUS',
            ShoppingList::class,
            [
                'VIEW' => AccessLevel::NONE_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL,
                'DELETE' => AccessLevel::NONE_LEVEL,
                'ASSIGN' => AccessLevel::NONE_LEVEL,
                'EDIT' => AccessLevel::NONE_LEVEL,
            ]
        );

        $this->setVisitorCookie($this->getReference('visitor2'));

        $response = $this->post(
            ['entity' => 'shoppinglists'],
            'create_shopping_list_inverse.yml'
        );

        $shoppingListId = (int)$this->getResourceId($response);
        $responseContent = $this->updateResponseContent('create_shopping_list_inverse_visitor.yml', $response);
        $this->assertResponseContains($responseContent, $response);

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntityManager()
            ->getRepository(ShoppingList::class)
            ->find($shoppingListId);
        self::assertNotNull($shoppingList);

        self::assertCount(1, $shoppingList->getLineItems());
    }

    public function testGetListWhenVisitorHasNoAccessToShoppingList()
    {
        $this->updateRolePermissions(
            'ROLE_FRONTEND_ANONYMOUS',
            ShoppingList::class,
            [
                'VIEW' => AccessLevel::NONE_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL,
                'DELETE' => AccessLevel::NONE_LEVEL,
                'ASSIGN' => AccessLevel::NONE_LEVEL,
                'EDIT' => AccessLevel::NONE_LEVEL,
            ]
        );

        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->cget(
            ['entity' => 'shoppinglists']
        );

        $this->assertResponseContains('cget_shopping_list_visitor.yml', $response);
    }

    public function testGetWhenVisitorHasNoAccessToShoppingList()
    {
        $this->updateRolePermissions(
            'ROLE_FRONTEND_ANONYMOUS',
            ShoppingList::class,
            [
                'VIEW' => AccessLevel::NONE_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL,
                'DELETE' => AccessLevel::NONE_LEVEL,
                'ASSIGN' => AccessLevel::NONE_LEVEL,
                'EDIT' => AccessLevel::NONE_LEVEL,
            ]
        );

        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->get(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>']
        );

        $this->assertResponseContains('get_shopping_list_visitor.yml', $response);
    }

    public function testAddToCartWhenVisitorHasNoAccessToShoppingList()
    {
        $this->updateRolePermissions(
            'ROLE_FRONTEND_ANONYMOUS',
            ShoppingList::class,
            [
                'VIEW' => AccessLevel::NONE_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL,
                'DELETE' => AccessLevel::NONE_LEVEL,
                'ASSIGN' => AccessLevel::NONE_LEVEL,
                'EDIT' => AccessLevel::NONE_LEVEL,
            ]
        );

        $this->setVisitorCookie($this->getReference('visitor1'));

        $shoppingListId = $this->getReference('shopping_list1')->getId();

        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => (string)$shoppingListId, 'association' => 'items'],
            'add_line_item_visitor.yml'
        );

        $responseContent = $this->updateResponseContent('add_line_item_visitor.yml', $response);
        $this->assertResponseContains($responseContent, $response);
    }

    public function testUpdateWhenVisitorHasNoAccessToShoppingList()
    {
        $this->updateRolePermissions(
            'ROLE_FRONTEND_ANONYMOUS',
            ShoppingList::class,
            [
                'VIEW' => AccessLevel::NONE_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL,
                'DELETE' => AccessLevel::NONE_LEVEL,
                'ASSIGN' => AccessLevel::NONE_LEVEL,
                'EDIT' => AccessLevel::NONE_LEVEL,
            ]
        );

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
            $data
        );

        $this->assertResponseContains($data, $response);
    }
}
