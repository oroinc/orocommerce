<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\ApiFrontendForVisitor\RestJsonApi;

use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
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
 */
class ShoppingListItemForVisitorWithShoppingListTest extends FrontendRestJsonApiTestCase
{
    use RolePermissionExtension;

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

        $this->setGuestShoppingListFeatureStatus(true);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->setGuestShoppingListFeatureStatus(false);
        parent::tearDown();
    }

    private function setGuestShoppingListFeatureStatus(bool $status): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_shopping_list.availability_for_guests', $status);
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
        ?string $notes = null,
        ?int $parentProductId = null
    ): void {
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

    public function testGetList(): void
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->cget(['entity' => 'shoppinglistitems'], [], ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains('cget_line_item_visitor.yml', $response);
        self::assertEquals(2, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGet(): void
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->get(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>']
        );

        $this->assertResponseContains('get_line_item_visitor.yml', $response);
    }

    public function testCreateLineItem(): void
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $organizationId = $this->getReference('organization')->getId();
        $productId = $this->getReference('product2')->getId();
        $productUnitCode = $this->getReference('set')->getCode();

        $response = $this->post(
            ['entity' => 'shoppinglistitems'],
            'create_line_item_visitor.yml'
        );

        $lineItemId = (int)$this->getResourceId($response);
        $responseContent = $this->updateResponseContent('create_line_item_visitor.yml', $response);
        $this->assertResponseContains($responseContent, $response);

        /** @var LineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(LineItem::class, $lineItemId);
        self::assertNotNull($lineItem);
        $shoppingList = $lineItem->getShoppingList();
        self::assertCount(3, $shoppingList->getLineItems());
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

    public function testTryToGetNotVisitorLineItem(): void
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->get(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item2->id)>'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testUpdate(): void
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $lineItemId = $this->getReference('line_item1')->getId();
        $data = [
            'data' => [
                'type'       => 'shoppinglistitems',
                'id'         => (string)$lineItemId,
                'attributes' => [
                    'quantity' => 123.4
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'shoppinglistitems', 'id' => (string)$lineItemId],
            $data
        );

        $this->assertResponseContains($data, $response);
    }

    public function testTryToUpdateNotVisitorLineItem(): void
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $lineItemId = $this->getReference('line_item2')->getId();
        $data = [
            'data' => [
                'type'       => 'shoppinglistitems',
                'id'         => (string)$lineItemId,
                'attributes' => [
                    'quantity' => 123
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'shoppinglistitems', 'id' => (string)$lineItemId],
            $data,
            [],
            false
        );

        /** @var LineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(LineItem::class, $lineItemId);
        self::assertEquals(10, $lineItem->getQuantity());
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testDelete(): void
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $lineItemId = $this->getReference('line_item1')->getId();

        $this->delete(
            ['entity' => 'shoppinglistitems', 'id' => (string)$lineItemId]
        );

        /** @var LineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(LineItem::class, $lineItemId);
        self::assertTrue(null === $lineItem);
    }

    public function testTryToDeleteNotVisitorLineItem(): void
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $lineItemId = $this->getReference('line_item2')->getId();

        $response = $this->delete(
            ['entity' => 'shoppinglistitems', 'id' => (string)$lineItemId],
            [],
            [],
            false
        );

        /** @var LineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(LineItem::class, $lineItemId);
        $this->assertTrue(null !== $lineItem);
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testGetListWhenVisitorHasNoAccessToShoppingList(): void
    {
        $this->updateRolePermissions(
            'ROLE_FRONTEND_ANONYMOUS',
            ShoppingList::class,
            [
                'VIEW' => AccessLevel::NONE_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL,
                'DELETE' => AccessLevel::NONE_LEVEL,
                'ASSIGN' => AccessLevel::NONE_LEVEL,
                'EDIT' => AccessLevel::NONE_LEVEL
            ]
        );

        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->cget(
            ['entity' => 'shoppinglistitems']
        );

        $this->assertResponseContains('cget_line_item_visitor.yml', $response);
    }

    public function testGetWhenVisitorHasNoAccessToShoppingList(): void
    {
        $this->updateRolePermissions(
            'ROLE_FRONTEND_ANONYMOUS',
            ShoppingList::class,
            [
                'VIEW' => AccessLevel::NONE_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL,
                'DELETE' => AccessLevel::NONE_LEVEL,
                'ASSIGN' => AccessLevel::NONE_LEVEL,
                'EDIT' => AccessLevel::NONE_LEVEL
            ]
        );

        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->get(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>']
        );

        $this->assertResponseContains('get_line_item_visitor.yml', $response);
    }

    public function testCreateLineItemWhenVisitorHasNoAccessToShoppingList(): void
    {
        $this->updateRolePermissions(
            'ROLE_FRONTEND_ANONYMOUS',
            ShoppingList::class,
            [
                'VIEW' => AccessLevel::NONE_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL,
                'DELETE' => AccessLevel::NONE_LEVEL,
                'ASSIGN' => AccessLevel::NONE_LEVEL,
                'EDIT' => AccessLevel::NONE_LEVEL
            ]
        );

        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->post(
            ['entity' => 'shoppinglistitems'],
            'create_line_item_visitor.yml'
        );

        $responseContent = $this->updateResponseContent('create_line_item_visitor.yml', $response);
        $this->assertResponseContains($responseContent, $response);
    }

    public function testUpdateWhenVisitorHasNoAccessToShoppingList(): void
    {
        $this->updateRolePermissions(
            'ROLE_FRONTEND_ANONYMOUS',
            ShoppingList::class,
            [
                'VIEW' => AccessLevel::NONE_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL,
                'DELETE' => AccessLevel::NONE_LEVEL,
                'ASSIGN' => AccessLevel::NONE_LEVEL,
                'EDIT' => AccessLevel::NONE_LEVEL
            ]
        );

        $this->setVisitorCookie($this->getReference('visitor1'));

        $lineItemId = $this->getReference('line_item1')->getId();
        $data = [
            'data' => [
                'type'       => 'shoppinglistitems',
                'id'         => (string)$lineItemId,
                'attributes' => [
                    'quantity' => 123.4
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'shoppinglistitems', 'id' => (string)$lineItemId],
            $data
        );

        $this->assertResponseContains($data, $response);
    }

    public function testDeleteWhenVisitorHasNoAccessToShoppingList(): void
    {
        $this->updateRolePermissions(
            'ROLE_FRONTEND_ANONYMOUS',
            ShoppingList::class,
            [
                'VIEW' => AccessLevel::NONE_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL,
                'DELETE' => AccessLevel::NONE_LEVEL,
                'ASSIGN' => AccessLevel::NONE_LEVEL,
                'EDIT' => AccessLevel::NONE_LEVEL
            ]
        );

        $this->setVisitorCookie($this->getReference('visitor1'));

        $lineItemId = $this->getReference('line_item1')->getId();

        $this->delete(
            ['entity' => 'shoppinglistitems', 'id' => (string)$lineItemId]
        );

        /** @var LineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(LineItem::class, $lineItemId);
        self::assertTrue(null === $lineItem);
    }

    public function testCreateTogetherWithShoppingListWhenTheRoleHasNoPermissions(): void
    {
        $this->updateRolePermissions(
            'ROLE_FRONTEND_ANONYMOUS',
            ShoppingList::class,
            [
                'VIEW' => AccessLevel::NONE_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL,
                'DELETE' => AccessLevel::NONE_LEVEL,
                'ASSIGN' => AccessLevel::NONE_LEVEL,
                'EDIT' => AccessLevel::NONE_LEVEL
            ]
        );

        $this->setVisitorCookie($this->getReference('visitor2'));

        $response = $this->post(
            ['entity' => 'shoppinglistitems'],
            'create_line_item_with_shopping_list_visitor.yml'
        );

        $responseContent = $this->updateResponseContent('create_line_item_with_shopping_list_visitor.yml', $response);
        $this->assertResponseContains($responseContent, $response);

        $lineItemId = (int)$this->getResourceId($response);
        $content = self::jsonToArray($response->getContent());
        $shoppingListId = (int)$content['included'][0]['id'];
        /** @var LineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(LineItem::class, $lineItemId);
        self::assertNotNull($lineItem);

        $shoppingList = $lineItem->getShoppingList();
        self::assertEquals($shoppingListId, $shoppingList->getId());
        self::assertCount(1, $shoppingList->getLineItems());
    }
}
