<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ShoppingListItemTest extends FrontendRestJsonApiTestCase
{
    /** @var int|null */
    private $originalShoppingListLimit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
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

        $this->originalShoppingListLimit = $this->getShoppingListLimit();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if ($this->getShoppingListLimit() !== $this->originalShoppingListLimit) {
            $this->setShoppingListLimit($this->originalShoppingListLimit);
        }
        $this->originalShoppingListLimit = null;
    }

    /**
     * @return int
     */
    private function getShoppingListLimit()
    {
        return $this->getConfigManager()->get('oro_shopping_list.shopping_list_limit');
    }

    /**
     * @param int $limit
     */
    private function setShoppingListLimit($limit)
    {
        $configManager = $this->getConfigManager();
        $configManager->set('oro_shopping_list.shopping_list_limit', $limit);
        $configManager->flush();
    }

    private static function assertLineItem(
        LineItem $lineItem,
        int $organizationId,
        int $userId,
        int $customerUserId,
        float $quantity,
        string $productUnitCode,
        int $productId,
        string $notes = null,
        int $parentProductId = null
    ) {
        self::assertEquals($organizationId, $lineItem->getOrganization()->getId());
        self::assertEquals($userId, $lineItem->getOwner()->getId());
        self::assertEquals($customerUserId, $lineItem->getCustomerUser()->getId());
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

    private static function assertShoppingList(
        ShoppingList $shoppingList,
        int $organizationId,
        int $userId,
        int $customerId,
        int $customerUserId,
        int $websiteId,
        string $notes = null
    ) {
        self::assertEquals($organizationId, $shoppingList->getOrganization()->getId());
        self::assertEquals($userId, $shoppingList->getOwner()->getId());
        self::assertEquals($customerUserId, $shoppingList->getCustomerUser()->getId());
        self::assertEquals($customerId, $shoppingList->getCustomer()->getId());
        self::assertEquals($websiteId, $shoppingList->getWebsite()->getId());
        if (null === $notes) {
            self::assertTrue(null === $shoppingList->getNotes());
        } else {
            self::assertEquals($notes, $shoppingList->getNotes());
        }
    }

    private function assertShoppingListTotal(
        ShoppingList $shoppingList,
        float $total,
        string $currency
    ) {
        /** @var ShoppingListTotal[] $totals */
        $totals = $this->getEntityManager()
            ->getRepository(ShoppingListTotal::class)
            ->findBy(['shoppingList' => $shoppingList]);
        self::assertCount(1, $totals);
        $totalEntity = $totals[0];
        self::assertEquals($total, $totalEntity->getSubtotal()->getAmount());
        self::assertEquals($currency, $totalEntity->getCurrency());
    }

    public function testGetList()
    {
        $response = $this->cget(['entity' => 'shoppinglistitems'], [], ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains('cget_line_item.yml', $response);
        self::assertEquals(5, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGetListFilteredByShoppingList()
    {
        $response = $this->cget(
            ['entity' => 'shoppinglistitems'],
            ['filter' => ['shoppingList' => '<toString(@shopping_list1->id)>']],
            ['HTTP_X-Include' => 'totalCount']
        );

        $this->assertResponseContains('cget_line_item_filter.yml', $response);
        self::assertEquals(3, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>']
        );

        $this->assertResponseContains('get_line_item.yml', $response);
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

        /** @var LineItem $lineItem */
        $lineItem = $this->getEntityManager()
            ->getRepository(LineItem::class)
            ->find($lineItemId);
        self::assertNotNull($lineItem);
        self::assertEquals(123.45, $lineItem->getQuantity());
        $this->assertShoppingListTotal($lineItem->getShoppingList(), 177.68, 'USD');
    }

    public function testTryToUpdateFloatQuantityWhenPrecisionIsZero()
    {
        $lineItemId = $this->getReference('line_item2')->getId();
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
                'title'  => 'quantity unit precision constraint',
                'detail' => 'The precision for the unit "item" is not valid.',
                'source' => ['pointer' => '/data/attributes/quantity']
            ],
            $response
        );
    }

    public function testCreate()
    {
        $userId = $this->getReference('user')->getId();
        $organizationId = $this->getReference('organization')->getId();
        $customerUserId = $this->getReference('customer_user')->getId();
        $productId = $this->getReference('product1')->getId();
        $productUnitCode = $this->getReference('set')->getCode();
        $shoppingListId = $this->getReference('shopping_list1')->getId();

        $response = $this->post(
            ['entity' => 'shoppinglistitems'],
            'create_line_item.yml'
        );

        $lineItemId = (int)$this->getResourceId($response);
        $responseContent = $this->updateResponseContent('create_line_item.yml', $response);
        $this->assertResponseContains($responseContent, $response);

        /** @var LineItem $lineItem */
        $lineItem = $this->getEntityManager()
            ->getRepository(LineItem::class)
            ->find($lineItemId);
        self::assertNotNull($lineItem);
        self::assertLineItem(
            $lineItem,
            $organizationId,
            $userId,
            $customerUserId,
            10,
            $productUnitCode,
            $productId,
            'New Line Item Notes'
        );
        $shoppingList = $lineItem->getShoppingList();
        self::assertEquals($shoppingListId, $shoppingList->getId());
        self::assertCount(4, $shoppingList->getLineItems());
        $this->assertShoppingListTotal($lineItem->getShoppingList(), 169.05, 'USD');
    }

    public function testTryToCreateWithFloatQuantityWhenPrecisionIsZero()
    {
        $response = $this->post(
            ['entity' => 'shoppinglistitems'],
            'create_line_item_precision_zero.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'quantity unit precision constraint',
                'detail' => 'The precision for the unit "item" is not valid.',
                'source' => ['pointer' => '/data/attributes/quantity']
            ],
            $response
        );
    }

    public function testCreateTogetherWithShoppingList()
    {
        $userId = $this->getReference('user')->getId();
        $organizationId = $this->getReference('organization')->getId();
        $customerUserId = $this->getReference('customer_user')->getId();
        $customerId = $this->getReference('customer')->getId();
        $websiteId = $this->getReference('website')->getId();
        $productId = $this->getReference('product1')->getId();
        $productUnitCode = $this->getReference('set')->getCode();

        $response = $this->post(
            ['entity' => 'shoppinglistitems'],
            'create_line_item_with_shopping_list.yml'
        );

        $responseContent = $this->updateResponseContent('create_line_item_with_shopping_list.yml', $response);
        $this->assertResponseContains($responseContent, $response);

        $lineItemId = (int)$this->getResourceId($response);
        $content = self::jsonToArray($response->getContent());
        $shoppingListId = (int)$content['included'][0]['id'];
        /** @var LineItem $lineItem */
        $lineItem = $this->getEntityManager()
            ->getRepository(LineItem::class)
            ->find($lineItemId);
        self::assertNotNull($lineItem);
        self::assertLineItem(
            $lineItem,
            $organizationId,
            $userId,
            $customerUserId,
            10,
            $productUnitCode,
            $productId,
            'New Line Item Notes'
        );
        $shoppingList = $lineItem->getShoppingList();
        self::assertEquals($shoppingListId, $shoppingList->getId());
        self::assertCount(1, $shoppingList->getLineItems());
        self::assertShoppingList(
            $shoppingList,
            $organizationId,
            $userId,
            $customerId,
            $customerUserId,
            $websiteId
        );
        $this->assertShoppingListTotal($lineItem->getShoppingList(), 109.9, 'USD');
    }

    public function testCreateTogetherWithShoppingListButLineItemDoesNotContainAssociationToShoppingList()
    {
        $userId = $this->getReference('user')->getId();
        $organizationId = $this->getReference('organization')->getId();
        $customerUserId = $this->getReference('customer_user')->getId();
        $customerId = $this->getReference('customer')->getId();
        $websiteId = $this->getReference('website')->getId();
        $productId = $this->getReference('product1')->getId();
        $productUnitCode = $this->getReference('set')->getCode();

        $response = $this->post(
            ['entity' => 'shoppinglistitems'],
            'create_line_item_with_shopping_list_inverse.yml'
        );

        $responseContent = $this->updateResponseContent('create_line_item_with_shopping_list_inverse.yml', $response);
        $this->assertResponseContains($responseContent, $response);

        $lineItemId = (int)$this->getResourceId($response);
        $content = self::jsonToArray($response->getContent());
        $shoppingListId = (int)$content['included'][0]['id'];
        /** @var LineItem $lineItem */
        $lineItem = $this->getEntityManager()
            ->getRepository(LineItem::class)
            ->find($lineItemId);
        self::assertNotNull($lineItem);
        self::assertLineItem(
            $lineItem,
            $organizationId,
            $userId,
            $customerUserId,
            15,
            $productUnitCode,
            $productId,
            'New Line Item Notes'
        );
        $shoppingList = $lineItem->getShoppingList();
        self::assertEquals($shoppingListId, $shoppingList->getId());
        self::assertCount(1, $shoppingList->getLineItems());
        self::assertShoppingList(
            $shoppingList,
            $organizationId,
            $userId,
            $customerId,
            $customerUserId,
            $websiteId
        );
        $this->assertShoppingListTotal($lineItem->getShoppingList(), 164.85, 'USD');
    }

    public function testCreateForDefaultShoppingListForUserWithoutShoppingLists()
    {
        $userId = $this->getReference('user')->getId();
        $organizationId = $this->getReference('organization')->getId();
        $customerUserId = $this->getReference('john')->getId();
        $productId = $this->getReference('product1')->getId();
        $productUnitCode = $this->getReference('set')->getCode();

        $data = $this->getRequestData('create_line_item.yml');
        $data['data']['relationships']['shoppingList']['data']['id'] = 'default';
        $response = $this->post(
            ['entity' => 'shoppinglistitems'],
            $data,
            self::generateWsseAuthHeader('john@example.com', 'john')
        );

        $lineItemId = (int)$this->getResourceId($response);

        /** @var LineItem $lineItem */
        $lineItem = $this->getEntityManager()
            ->getRepository(LineItem::class)
            ->find($lineItemId);
        self::assertNotNull($lineItem);
        $shoppingList = $lineItem->getShoppingList();
        self::assertNotNull($shoppingList);

        $responseContent = $this->updateResponseContent('create_line_item.yml', $response);
        $responseContent['data']['relationships']['shoppingList']['data']['id'] = (string)$shoppingList->getId();
        $this->assertResponseContains($responseContent, $response);

        self::assertCount(1, $shoppingList->getLineItems());
        self::assertLineItem(
            $lineItem,
            $organizationId,
            $userId,
            $customerUserId,
            10,
            $productUnitCode,
            $productId,
            'New Line Item Notes'
        );
        self::assertCount(1, $shoppingList->getLineItems());
        $this->assertShoppingListTotal($shoppingList, 109.9, 'USD');
    }

    public function testTryToCreateTogetherWithShoppingListWhenShoppingListLimitExceeded()
    {
        $this->setShoppingListLimit(2);

        $response = $this->post(
            ['entity' => 'shoppinglistitems'],
            'create_line_item_with_shopping_list.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'create shopping list constraint',
                'detail' => 'It is not allowed to create a new shopping list.',
                'source' => ['pointer' => '/included/0']
            ],
            $response
        );
    }

    public function testCreateForShoppingListBelongsToAnotherCustomerUser()
    {
        $userId = $this->getReference('user')->getId();
        $organizationId = $this->getReference('organization')->getId();
        $customerUserId = $this->getReference('amanda')->getId();
        $productId = $this->getReference('product1')->getId();
        $productUnitCode = $this->getReference('set')->getCode();
        $shoppingListId = $this->getReference('shopping_list2')->getId();

        $data = $this->getRequestData('create_line_item.yml');
        $data['data']['relationships']['shoppingList']['data']['id'] = (string)$shoppingListId;
        $response = $this->post(
            ['entity' => 'shoppinglistitems'],
            $data
        );

        $lineItemId = (int)$this->getResourceId($response);
        $responseContent = $this->updateResponseContent('create_line_item.yml', $response);
        $responseContent['data']['relationships']['shoppingList']['data']['id'] = (string)$shoppingListId;
        $this->assertResponseContains($responseContent, $response);

        /** @var LineItem $lineItem */
        $lineItem = $this->getEntityManager()
            ->getRepository(LineItem::class)
            ->find($lineItemId);
        self::assertNotNull($lineItem);
        self::assertLineItem(
            $lineItem,
            $organizationId,
            $userId,
            $customerUserId,
            10,
            $productUnitCode,
            $productId,
            'New Line Item Notes'
        );
        $shoppingList = $lineItem->getShoppingList();
        self::assertEquals($shoppingListId, $shoppingList->getId());
        self::assertCount(2, $shoppingList->getLineItems());
        $this->assertShoppingListTotal($lineItem->getShoppingList(), 130.1, 'USD');
    }

    public function testDelete()
    {
        $lineItemId = $this->getReference('line_item1')->getId();
        $shoppingListId = $this->getReference('shopping_list1')->getId();

        $this->delete(
            ['entity' => 'shoppinglistitems', 'id' => (string)$lineItemId]
        );

        /** @var LineItem $lineItem */
        $lineItem = $this->getEntityManager()
            ->getRepository(LineItem::class)
            ->find($lineItemId);
        self::assertTrue(null === $lineItem);
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntityManager()
            ->getRepository(ShoppingList::class)
            ->find($shoppingListId);
        self::assertCount(2, $shoppingList->getLineItems());
        $this->assertShoppingListTotal($shoppingList, 53.0, 'USD');
    }

    public function testDeleteList()
    {
        $lineItemId = $this->getReference('line_item1')->getId();
        $shoppingListId = $this->getReference('shopping_list1')->getId();

        $this->cdelete(
            ['entity' => 'shoppinglistitems'],
            ['filter' => ['id' => (string)$lineItemId]]
        );

        /** @var LineItem $lineItem */
        $lineItem = $this->getEntityManager()
            ->getRepository(LineItem::class)
            ->find($lineItemId);
        self::assertTrue(null === $lineItem);
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntityManager()
            ->getRepository(ShoppingList::class)
            ->find($shoppingListId);
        self::assertCount(2, $shoppingList->getLineItems());
        $this->assertShoppingListTotal($shoppingList, 53.0, 'USD');
    }

    public function testTryToSetZeroQuantity()
    {
        $data = [
            'data' => [
                'type'       => 'shoppinglistitems',
                'id'         => '<toString(@line_item1->id)>',
                'attributes' => [
                    'quantity' => 0
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'greater than constraint',
                'detail' => 'This value should be greater than 0.',
                'source' => ['pointer' => '/data/attributes/quantity']
            ],
            $response
        );
    }

    public function testTryToSetNegativeQuantity()
    {
        $data = [
            'data' => [
                'type'       => 'shoppinglistitems',
                'id'         => '<toString(@line_item1->id)>',
                'attributes' => [
                    'quantity' => -10
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'greater than constraint',
                'detail' => 'This value should be greater than 0.',
                'source' => ['pointer' => '/data/attributes/quantity']
            ],
            $response
        );
    }

    public function testTryToSetNullQuantity()
    {
        $data = [
            'data' => [
                'type'       => 'shoppinglistitems',
                'id'         => '<toString(@line_item1->id)>',
                'attributes' => [
                    'quantity' => null
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/quantity']
            ],
            $response
        );
    }

    public function testTryToSetNullUnit()
    {
        $data = [
            'data' => [
                'type'          => 'shoppinglistitems',
                'id'            => '<toString(@line_item1->id)>',
                'relationships' => [
                    'unit' => ['data' => null]
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/relationships/unit/data']
            ],
            $response
        );
    }

    public function testTryToSetNullProduct()
    {
        $data = [
            'data' => [
                'type'          => 'shoppinglistitems',
                'id'            => '<toString(@line_item1->id)>',
                'relationships' => [
                    'product' => ['data' => null]
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/relationships/product/data']
            ],
            $response
        );
    }

    public function testTryToSetNullShoppingList()
    {
        $data = [
            'data' => [
                'type'          => 'shoppinglistitems',
                'id'            => '<toString(@line_item1->id)>',
                'relationships' => [
                    'shoppingList' => ['data' => null]
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/relationships/shoppingList/data']
            ],
            $response
        );
    }

    public function testTryToMoveToAnotherShoppingListWhenTargetShoppingListHasLineItemWithSameProduct()
    {
        $data = [
            'data' => [
                'type'          => 'shoppinglistitems',
                'id'            => '<toString(@line_item1->id)>',
                'relationships' => [
                    'shoppingList' => [
                        'data' => ['type' => 'shoppinglists', 'id' => '<toString(@shopping_list2->id)>']
                    ]
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'line item constraint',
                'detail' => 'Line Item with the same product and unit already exists'
            ],
            $response
        );
    }

    public function testTryToMoveToAnotherShoppingListFromAnotherWebsite()
    {
        $data = [
            'data' => [
                'type'          => 'shoppinglistitems',
                'id'            => '<toString(@line_item2->id)>',
                'relationships' => [
                    'shoppingList' => [
                        'data' => ['type' => 'shoppinglists', 'id' => '<toString(@shopping_list3->id)>']
                    ]
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item2->id)>'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'access granted constraint',
                    'detail' => 'The "EDIT" permission is denied for the related resource.',
                    'source' => ['pointer' => '/data/relationships/shoppingList/data']
                ],
                [
                    'title'  => 'access granted constraint',
                    'detail' => 'The "VIEW" permission is denied for the related resource.',
                    'source' => ['pointer' => '/data/relationships/shoppingList/data']
                ]
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToCreateForShoppingListFromAnotherWebsite()
    {
        $shoppingListId = $this->getReference('shopping_list3')->getId();

        $data = $this->getRequestData('create_line_item.yml');
        $data['data']['relationships']['shoppingList']['data']['id'] = (string)$shoppingListId;

        $response = $this->post(
            ['entity' => 'shoppinglistitems'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'access granted constraint',
                    'detail' => 'The "EDIT" permission is denied for the related resource.',
                    'source' => ['pointer' => '/data/relationships/shoppingList/data']
                ],
                [
                    'title'  => 'access granted constraint',
                    'detail' => 'The "VIEW" permission is denied for the related resource.',
                    'source' => ['pointer' => '/data/relationships/shoppingList/data']
                ]
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToCreateWithoutRequiredFields()
    {
        $data = [
            'data' => [
                'type' => 'shoppinglistitems'
            ]
        ];

        $response = $this->post(
            ['entity' => 'shoppinglistitems'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/relationships/product/data']
                ],
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/relationships/shoppingList/data']
                ],
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/relationships/unit/data']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithWrongProductUnit()
    {
        $response = $this->post(
            ['entity' => 'shoppinglistitems'],
            'create_line_item_wrong_unit.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'product unit exists constraint',
                'detail' => 'The product unit does not exist for the product.',
                'source' => ['pointer' => '/data/relationships/unit/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithNotSellProductProductUnit()
    {
        $response = $this->post(
            ['entity' => 'shoppinglistitems'],
            'create_line_item_not_sell_unit.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'product unit exists constraint',
                'detail' => 'The product unit does not exist for the product.',
                'source' => ['pointer' => '/data/relationships/unit/data']
            ],
            $response
        );
    }

    public function testTryToAddDuplicatedLineItem()
    {
        $response = $this->post(
            ['entity' => 'shoppinglistitems'],
            'create_line_item_duplicate.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'conflict constraint',
                'detail' => 'The entity already exists'
            ],
            $response,
            Response::HTTP_CONFLICT
        );
    }

    public function testTryToUpdateThatCausesDuplicatedLineItem()
    {
        $response = $this->patch(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item2->id)>'],
            'update_line_item_duplicate.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'line item constraint',
                'detail' => 'Line Item with the same product and unit already exists'
            ],
            $response
        );
    }

    public function testTryToGetSubresourceShoppingList()
    {
        $response = $this->getSubresource(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>', 'association' => 'shoppingList'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToGetRelationshipShoppingList()
    {
        $response = $this->getRelationship(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>', 'association' => 'shoppingList'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToUpdateRelationshipShoppingList()
    {
        $response = $this->patchRelationship(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>', 'association' => 'shoppingList'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToGetSubresourceParentProduct()
    {
        $response = $this->getSubresource(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>', 'association' => 'parentProduct'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToGetRelationshipParentProduct()
    {
        $response = $this->getRelationship(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>', 'association' => 'parentProduct'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToUpdateRelationshipParentProduct()
    {
        $response = $this->patchRelationship(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>', 'association' => 'parentProduct'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToGetSubresourceProduct()
    {
        $response = $this->getSubresource(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>', 'association' => 'product'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToGetRelationshipProduct()
    {
        $response = $this->getRelationship(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>', 'association' => 'product'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToUpdateRelationshipProduct()
    {
        $response = $this->patchRelationship(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>', 'association' => 'product'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToGetSubresourceUnit()
    {
        $response = $this->getSubresource(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>', 'association' => 'unit'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToGetRelationshipUnit()
    {
        $response = $this->getRelationship(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>', 'association' => 'unit'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToUpdateRelationshipUnit()
    {
        $response = $this->patchRelationship(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>', 'association' => 'unit'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testGetKitLineItem(): void
    {
        $response = $this->get(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@kit_line_item1->id)>']
        );

        $this->assertResponseContains('get_kit_line_item.yml', $response);
    }
}
