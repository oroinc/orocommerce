<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\ApiFrontendForVisitor\RestJsonApi;

use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class ShoppingListForAnonymousVisitorTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->enableAnonymousVisitor();
        $this->setAnonymousVisitorCookie();
        $this->loadFixtures([
            '@OroShoppingListBundle/Tests/Functional/ApiFrontendForVisitor/DataFixtures/shopping_list_for_visitor.yml'
        ]);

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

    private function getLastVisitorId(): int
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('MAX(v.id) AS lastVisitorId')
            ->from(CustomerVisitor::class, 'v')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'shoppinglists'], [], ['HTTP_X-Include' => 'totalCount']);
        $this->assertResponseContains(['data' => []], $response);
        self::assertEquals(0, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testGetForDefaultShoppingList(): void
    {
        $response = $this->get(
            ['entity' => 'shoppinglists', 'id' => 'default'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'not found http exception',
                'detail' => 'An entity with the requested identifier does not exist.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testTryToAddToCartForShoppingListBelongsToAnotherVisitor(): void
    {
        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'items'],
            'add_line_item_visitor.yml',
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the parent entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testAddToCartForDefaultShoppingList(): void
    {
        $lastVisitorId = $this->getLastVisitorId();

        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => 'default', 'association' => 'items'],
            'add_line_item_visitor.yml'
        );

        $responseContent = $this->updateResponseContent('add_line_item_visitor.yml', $response);
        $this->assertResponseContains($responseContent, $response);

        /** @var LineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(LineItem::class, (int)$responseContent['data'][0]['id']);
        $shoppingList = $lineItem->getShoppingList();
        self::assertEquals(
            self::getContainer()->get('translator')->trans('oro.shoppinglist.default.label'),
            $shoppingList->getLabel()
        );
        self::assertCount(1, $shoppingList->getLineItems());

        $createdVisitorId = $this->getLastVisitorId();
        self::assertNotEquals($lastVisitorId, $createdVisitorId);
    }

    public function testCreateAndThenUpdateAndGetAndDeleteTheCreatedShoppingList(): void
    {
        $lastVisitorId = $this->getLastVisitorId();

        // create shopping list
        $response = $this->post(
            ['entity' => 'shoppinglists'],
            '../../ApiFrontend/RestJsonApi/requests/create_shopping_list_min.yml'
        );
        $shoppingListId = (int)$this->getResourceId($response);
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntityManager()->find(ShoppingList::class, $shoppingListId);
        self::assertEquals('New Shopping List', $shoppingList->getLabel());
        self::assertCount(1, $shoppingList->getLineItems());

        // check that a visitor is saved into the database
        $createdVisitorId = $this->getLastVisitorId();
        self::assertNotEquals($lastVisitorId, $createdVisitorId);

        // update the created shopping list
        $data = [
            'data' => [
                'type' => 'shoppinglists',
                'id' => (string)$shoppingListId,
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

        // get all shopping lists
        $response = $this->cget(['entity' => 'shoppinglists']);
        $this->assertResponseContains(['data' => [$data['data']]], $response);

        // delete the created shopping list
        $this->delete(
            ['entity' => 'shoppinglists', 'id' => (string)$shoppingListId]
        );
        self::assertNull($this->getEntityManager()->find(ShoppingList::class, $shoppingListId));
    }
}
