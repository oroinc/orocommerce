<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadBuyerCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class ShoppingListItemForBuyerTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadBuyerCustomerUserData::class,
            '@OroShoppingListBundle/Tests/Functional/Api/Frontend/DataFixtures/shopping_list.yml'
        ]);

        /** @var ShoppingListTotalManager $totalManager */
        $totalManager = self::getContainer()->get('oro_shopping_list.manager.shopping_list_total');
        for ($i = 1; $i <= 3; $i++) {
            $totalManager->recalculateTotals(
                $this->getReference(sprintf('shopping_list%d', $i)),
                true
            );
        }
    }

    public function testGetList()
    {
        $response = $this->cget(['entity' => 'shoppinglistitems'], [], ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains('cget_line_item_buyer.yml', $response);
        self::assertEquals(4, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>']
        );

        $this->assertResponseContains('get_line_item.yml', $response);
    }

    public function testTryToGetFromAnotherCustomerUser()
    {
        $response = $this->get(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item3->id)>'],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToGetFromAnotherWebsite()
    {
        $response = $this->get(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item4->id)>'],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToGetFromAnotherCustomer()
    {
        $response = $this->get(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item5->id)>'],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testUpdate()
    {
        $lineItemId = $this->getReference('line_item1')->getId();
        $data = [
            'data' => [
                'type'       => 'shoppinglistitems',
                'id'         => (string)$lineItemId,
                'attributes' => [
                    'quantity' => 123.45
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'shoppinglistitems', 'id' => (string)$lineItemId],
            $data
        );

        $this->assertResponseContains($data, $response);
    }

    public function testTryToUpdateFromAnotherCustomerUser()
    {
        $lineItemId = $this->getReference('line_item3')->getId();
        $data = [
            'data' => [
                'type'       => 'shoppinglistitems',
                'id'         => (string)$lineItemId,
                'attributes' => [
                    'quantity' => 123.45
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'shoppinglistitems', 'id' => (string)$lineItemId],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToUpdateFromAnotherWebsite()
    {
        $lineItemId = $this->getReference('line_item4')->getId();
        $data = [
            'data' => [
                'type'       => 'shoppinglistitems',
                'id'         => (string)$lineItemId,
                'attributes' => [
                    'quantity' => 123.45
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'shoppinglistitems', 'id' => (string)$lineItemId],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToUpdateFromAnotherCustomer()
    {
        $lineItemId = $this->getReference('line_item5')->getId();
        $data = [
            'data' => [
                'type'       => 'shoppinglistitems',
                'id'         => (string)$lineItemId,
                'attributes' => [
                    'quantity' => 123.45
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'shoppinglistitems', 'id' => (string)$lineItemId],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testCreate()
    {
        $response = $this->post(
            ['entity' => 'shoppinglistitems'],
            'create_line_item.yml'
        );

        $responseContent = $this->updateResponseContent('create_line_item.yml', $response);
        $this->assertResponseContains($responseContent, $response);
    }

    public function testDelete()
    {
        $lineItemId = $this->getReference('line_item1')->getId();

        $this->delete(
            ['entity' => 'shoppinglistitems', 'id' => (string)$lineItemId]
        );

        /** @var LineItem $lineItem */
        $lineItem = $this->getEntityManager()
            ->getRepository(LineItem::class)
            ->find($lineItemId);

        self::assertTrue(null === $lineItem);
    }

    public function testTryToDeleteFromAnotherCustomerUser()
    {
        $lineItemId = $this->getReference('line_item3')->getId();

        $response = $this->delete(
            ['entity' => 'shoppinglistitems', 'id' => (string)$lineItemId],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToDeleteFromAnotherWebsite()
    {
        $lineItemId = $this->getReference('line_item4')->getId();

        $response = $this->delete(
            ['entity' => 'shoppinglistitems', 'id' => (string)$lineItemId],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToDeleteFromAnotherCustomer()
    {
        $lineItemId = $this->getReference('line_item5')->getId();

        $response = $this->delete(
            ['entity' => 'shoppinglistitems', 'id' => (string)$lineItemId],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }
}
