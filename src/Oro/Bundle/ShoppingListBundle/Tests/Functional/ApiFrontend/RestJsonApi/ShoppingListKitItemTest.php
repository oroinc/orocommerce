<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ShoppingListKitItemTest extends FrontendRestJsonApiTestCase
{
    private ?int $originalShoppingListLimit;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroShoppingListBundle/Tests/Functional/ApiFrontend/DataFixtures/shopping_list.yml',
        ]);

        /** @var ShoppingListTotalManager $totalManager */
        $totalManager = self::getContainer()->get('oro_shopping_list.manager.shopping_list_total');
        for ($i = 1; $i <= 5; $i++) {
            $totalManager->recalculateTotals(
                $this->getReference(sprintf('shopping_list%d', $i)),
                true
            );
        }

        $this->originalShoppingListLimit = $this->getShoppingListLimit();
    }

    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();
        if ($this->getShoppingListLimit() !== $this->originalShoppingListLimit) {
            $this->setShoppingListLimit($this->originalShoppingListLimit);
        }
        $this->originalShoppingListLimit = null;
    }

    private function getShoppingListLimit(): int
    {
        return self::getConfigManager()->get('oro_shopping_list.shopping_list_limit');
    }

    private function setShoppingListLimit(int $limit): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_shopping_list.shopping_list_limit', $limit);
        $configManager->flush();
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'shoppinglistkititems']);

        $this->assertResponseContains('cget_kit_line_item.yml', $response);
    }

    public function testGetListFilteredByShoppingListItem(): void
    {
        $response = $this->cget(
            ['entity' => 'shoppinglistkititems'],
            ['filter' => ['lineItem' => '<toString(@kit_line_item1->id)>']]
        );

        $this->assertResponseContains('cget_kit_line_item_filter.yml', $response);
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'shoppinglistkititems', 'id' => '<toString(@product_kit_item1_line_item1->id)>']
        );

        $this->assertResponseContains('get_kit_item_line_item.yml', $response);
    }

    public function testCreateWithoutRequiredFields(): void
    {
        $data = [
            'data' => [
                'type' => 'shoppinglistkititems'
            ]
        ];

        $response = $this->post(
            ['entity' => 'shoppinglistkititems'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'not null constraint',
                    'detail' => 'This value should not be null.',
                    'source' => ['pointer' => '/data/relationships/lineItem/data']
                ],
                [
                    'title' => 'not null constraint',
                    'detail' => 'This value should not be null.',
                    'source' => ['pointer' => '/data/relationships/kitItem/data']
                ],
                [
                    'title' => 'not null constraint',
                    'detail' => 'This value should not be null.',
                    'source' => ['pointer' => '/data/relationships/product/data']
                ],
                [
                    'title' => 'not null constraint',
                    'detail' => 'This value should not be null.',
                    'source' => ['pointer' => '/data/relationships/unit/data']
                ]
            ],
            $response
        );
    }

    public function testCreate(): void
    {
        $shoppingList1 = $this->getReference('shopping_list1');
        $this->assertShoppingListTotal($shoppingList1, 59.15, 'USD');

        /** @var LineItem $lineItem */
        $lineItem = $this->getReference('kit_line_item1');
        $productKitItemId = $this->getReference('product_kit1_item3')->getId();
        $productId = $this->getReference('product4')->getId();
        $productUnitCode = $this->getReference('item')->getCode();

        self::assertCount(2, $lineItem->getKitItemLineItems());

        $response = $this->post(
            ['entity' => 'shoppinglistkititems'],
            'create_kit_item_line_item.yml'
        );

        $kitItemLineItemId = (int)$this->getResourceId($response);
        $responseContent = $this->updateResponseContent('create_kit_item_line_item.yml', $response);
        $this->assertResponseContains($responseContent, $response);

        /** @var ProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getEntityManager()->find(ProductKitItemLineItem::class, $kitItemLineItemId);
        self::assertNotNull($kitItemLineItem, 'ProductKitItemLineItem is not found');
        self::assertProductKitItemLineItem(
            $kitItemLineItem,
            $lineItem->getId(),
            $productKitItemId,
            $productId,
            $productUnitCode,
            5,
            1
        );
        self::assertCount(3, $kitItemLineItem->getLineItem()->getKitItemLineItems());

        $kitItemLineItemShoppingList = $kitItemLineItem->getLineItem()->getShoppingList();
        self::assertEquals($shoppingList1->getId(), $kitItemLineItemShoppingList->getId());
        $this->assertShoppingListTotal($kitItemLineItemShoppingList, 91.25, 'USD');
    }

    public function testCreateTogetherWithLineItem(): void
    {
        $shoppingList5 = $this->getReference('shopping_list5');
        $this->assertShoppingListTotal($shoppingList5, 1.23, 'USD');

        $productUnitCode = $this->getReference('item')->getCode();

        $response = $this->post(
            ['entity' => 'shoppinglistkititems'],
            'create_kit_item_line_item_with_line_item.yml'
        );

        $responseContent = $this->updateResponseContent('create_kit_item_line_item_with_line_item.yml', $response);
        $this->assertResponseContains($responseContent, $response);

        $kitItemLineItemId = (int)$this->getResourceId($response);
        $content = self::jsonToArray($response->getContent());
        $lineItemId = (int)$content['included'][0]['id'];

        /** @var ProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getEntityManager()->find(ProductKitItemLineItem::class, $kitItemLineItemId);
        self::assertNotNull($kitItemLineItem, 'ProductKitItemLineItem is not found');
        self::assertProductKitItemLineItem(
            $kitItemLineItem,
            $lineItemId,
            $this->getReference('product_kit1_item1')->getId(),
            $this->getReference('product1')->getId(),
            $productUnitCode,
            5,
            1
        );

        $lineItem = $kitItemLineItem->getLineItem();
        self::assertLineItem(
            $lineItem,
            $this->getReference('organization')->getId(),
            $this->getReference('user')->getId(),
            $this->getReference('customer_user')->getId(),
            10,
            $productUnitCode,
            $this->getReference('product_kit1')->getId(),
            1,
            'New Line Item Notes'
        );

        $kitItemLineItemShoppingList = $lineItem->getShoppingList();
        self::assertEquals($shoppingList5->getId(), $kitItemLineItemShoppingList->getId());
        $this->assertShoppingListTotal($kitItemLineItemShoppingList, 186.13, 'USD');
    }

    public function testCreateTogetherWithShoppingList(): void
    {
        $productUnitCode = $this->getReference('item')->getCode();

        $response = $this->post(
            ['entity' => 'shoppinglistkititems'],
            'create_kit_item_line_item_with_shopping_list.yml'
        );

        $responseContent = $this->updateResponseContent('create_kit_item_line_item_with_shopping_list.yml', $response);
        $this->assertResponseContains($responseContent, $response);

        $kitItemLineItemId = (int)$this->getResourceId($response);
        $content = self::jsonToArray($response->getContent());
        $lineItemId = (int)$content['included'][0]['id'];

        /** @var ProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getEntityManager()->find(ProductKitItemLineItem::class, $kitItemLineItemId);
        self::assertNotNull($kitItemLineItem, 'ProductKitItemLineItem is not found');
        self::assertProductKitItemLineItem(
            $kitItemLineItem,
            $lineItemId,
            $this->getReference('product_kit1_item1')->getId(),
            $this->getReference('product1')->getId(),
            $productUnitCode,
            5,
            1
        );

        $lineItem = $kitItemLineItem->getLineItem();
        self::assertLineItem(
            $lineItem,
            $this->getReference('organization')->getId(),
            $this->getReference('user')->getId(),
            $this->getReference('customer_user')->getId(),
            10,
            $productUnitCode,
            $this->getReference('product_kit1')->getId(),
            1,
            'New Line Item Notes'
        );

        $kitItemLineItemShoppingList = $lineItem->getShoppingList();
        $this->assertShoppingListTotal($kitItemLineItemShoppingList, 184.9, 'USD');
    }

    public function testCreateForDefaultShoppingListForUserWithoutShoppingLists(): void
    {
        $productUnitCode = $this->getReference('item')->getCode();

        $data = $this->getRequestData('create_kit_item_line_item_with_line_item.yml');
        $data['included'][0]['relationships']['shoppingList']['data']['id'] = 'default';
        $response = $this->post(
            ['entity' => 'shoppinglistkititems'],
            $data,
            self::generateApiAuthHeader('john@example.com')
        );

        $kitItemLineItemId = (int)$this->getResourceId($response);
        $content = self::jsonToArray($response->getContent());
        $lineItemId = (int)$content['included'][0]['id'];

        /** @var LineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(LineItem::class, $lineItemId);
        self::assertNotNull($lineItem);

        $kitItemLineItemShoppingList = $lineItem->getShoppingList();
        self::assertNotNull($kitItemLineItemShoppingList);

        $responseContent = $this->updateResponseContent('create_kit_item_line_item_with_line_item.yml', $response);
        $responseContent['included'][0]['relationships']['shoppingList']['data']['id'] =
            (string)$kitItemLineItemShoppingList->getId();
        $this->assertResponseContains($responseContent, $response);

        /** @var ProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getEntityManager()->find(ProductKitItemLineItem::class, $kitItemLineItemId);
        self::assertNotNull($kitItemLineItem, 'ProductKitItemLineItem is not found');
        self::assertProductKitItemLineItem(
            $kitItemLineItem,
            $lineItemId,
            $this->getReference('product_kit1_item1')->getId(),
            $this->getReference('product1')->getId(),
            $productUnitCode,
            5,
            1
        );

        $lineItem = $kitItemLineItem->getLineItem();
        self::assertLineItem(
            $lineItem,
            $this->getReference('organization')->getId(),
            $this->getReference('user')->getId(),
            $this->getReference('john')->getId(),
            10,
            $productUnitCode,
            $this->getReference('product_kit1')->getId(),
            1,
            'New Line Item Notes'
        );

        $this->assertShoppingListTotal($kitItemLineItemShoppingList, 184.9, 'USD');
    }

    public function testCreateTogetherWithShoppingListWhenShoppingListLimitExceeded(): void
    {
        $this->setShoppingListLimit(2);

        $response = $this->post(
            ['entity' => 'shoppinglistkititems'],
            'create_kit_item_line_item_with_shopping_list.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'create shopping list constraint',
                'detail' => 'It is not allowed to create a new shopping list.',
                'source' => ['pointer' => '/included/1']
            ],
            $response
        );
    }

    public function testCreateForShoppingListBelongsToAnotherCustomerUser(): void
    {
        $productUnitCode = $this->getReference('item')->getCode();
        $shoppingList2Id = $this->getReference('shopping_list2')->getId();

        $data = $this->getRequestData('create_kit_item_line_item_with_line_item.yml');
        $data['included'][0]['relationships']['shoppingList']['data']['id'] = (string)$shoppingList2Id;
        $response = $this->post(
            ['entity' => 'shoppinglistkititems'],
            $data
        );

        $kitItemLineItemId = (int)$this->getResourceId($response);
        $content = self::jsonToArray($response->getContent());
        $lineItemId = (int)$content['included'][0]['id'];

        /** @var LineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(LineItem::class, $lineItemId);
        self::assertNotNull($lineItem);

        $responseContent = $this->updateResponseContent('create_kit_item_line_item_with_line_item.yml', $response);
        $responseContent['included'][0]['relationships']['shoppingList']['data']['id'] = (string)$shoppingList2Id;
        $this->assertResponseContains($responseContent, $response);

        /** @var ProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getEntityManager()->find(ProductKitItemLineItem::class, $kitItemLineItemId);
        self::assertNotNull($kitItemLineItem, 'ProductKitItemLineItem is not found');
        self::assertProductKitItemLineItem(
            $kitItemLineItem,
            $lineItemId,
            $this->getReference('product_kit1_item1')->getId(),
            $this->getReference('product1')->getId(),
            $productUnitCode,
            5,
            1
        );

        $lineItem = $kitItemLineItem->getLineItem();
        self::assertLineItem(
            $lineItem,
            $this->getReference('organization')->getId(),
            $this->getReference('user')->getId(),
            $this->getReference('amanda')->getId(),
            10,
            $productUnitCode,
            $this->getReference('product_kit1')->getId(),
            1,
            'New Line Item Notes'
        );

        $kitItemLineItemShoppingList = $lineItem->getShoppingList();
        self::assertEquals($shoppingList2Id, $kitItemLineItemShoppingList->getId());
        $this->assertShoppingListTotal($kitItemLineItemShoppingList, 234.7, 'USD');
    }

    public function testCreateForShoppingListFromAnotherWebsite(): void
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
                    'title' => 'access granted constraint',
                    'detail' => 'The "EDIT" permission is denied for the related resource.',
                    'source' => ['pointer' => '/data/relationships/shoppingList/data']
                ],
                [
                    'title' => 'access granted constraint',
                    'detail' => 'The "VIEW" permission is denied for the related resource.',
                    'source' => ['pointer' => '/data/relationships/shoppingList/data']
                ]
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    /**
     * @dataProvider getUpdateValidationDataProvider
     */
    public function testUpdateValidation(array $parameters, array $expectedErrors): void
    {
        $data = array_merge(
            [
                'type' => 'shoppinglistkititems',
                'id' => '<toString(@product_kit_item1_line_item2->id)>'
            ],
            $parameters
        );

        $response = $this->patch(
            ['entity' => 'shoppinglistkititems', 'id' => '<toString(@product_kit_item1_line_item2->id)>'],
            ['data' => $data],
            [],
            false
        );

        $this->assertResponseValidationErrors($expectedErrors, $response);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getUpdateValidationDataProvider(): array
    {
        return [
            'quantity null' => [
                'parameters' => [
                    'attributes' => [
                        'quantity' => null
                    ]
                ],
                'expectedErrors' => [
                    [
                        'title' => 'not blank constraint',
                        'detail' => 'The quantity cannot be empty',
                        'source' => ['pointer' => '/data/attributes/quantity']
                    ]
                ]
            ],
            'quantity out of range' => [
                'parameters' => [
                    'attributes' => [
                        'quantity' => 10
                    ]
                ],
                'expectedErrors' => [
                    [
                        'title' => 'range constraint',
                        'detail' => 'The quantity should be between 0 and 5.',
                        'source' => ['pointer' => '/data/attributes/quantity']
                    ]
                ]
            ],
            'quantity wrong type' => [
                'parameters' => [
                    'attributes' => [
                        'quantity' => 'string'
                    ]
                ],
                'expectedErrors' => [
                    [
                        'title' => 'form constraint',
                        'detail' => 'Please enter a number.',
                        'source' => ['pointer' => '/data/attributes/quantity']
                    ]
                ]
            ],
            'quantity negative' => [
                'parameters' => [
                    'attributes' => [
                        'quantity' => -10
                    ]
                ],
                'expectedErrors' => [
                    [
                        'title' => 'greater than constraint',
                        'detail' => 'The quantity should be greater than 0.',
                        'source' => ['pointer' => '/data/attributes/quantity']
                    ],
                    [
                        'title' => 'range constraint',
                        'detail' => 'The quantity should be between 0 and 5.',
                        'source' => ['pointer' => '/data/attributes/quantity']
                    ]
                ]
            ],
            'quantity when no KitItem' => [
                'parameters' => [
                    'attributes' => [
                        'quantity' => 10
                    ],
                    'relationships' => [
                        'kitItem' => ['data' => null]
                    ],
                ],
                'expectedErrors' => [
                    [
                        'title' => 'not null constraint',
                        'detail' => 'This value should not be null.',
                        'source' => ['pointer' => '/data/relationships/kitItem/data']
                    ]
                ]
            ],
            'sortOrder wrong type' => [
                'parameters' => [
                    'attributes' => [
                        'sortOrder' => 'string'
                    ]
                ],
                'expectedErrors' => [
                    [
                        'title' => 'form constraint',
                        'detail' => 'Please enter an integer.',
                        'source' => ['pointer' => '/data/attributes/sortOrder']
                    ]
                ]
            ],
            'sortOrder out of range' => [
                'parameters' => [
                    'attributes' => [
                        'sortOrder' => 2147483648
                    ]
                ],
                'expectedErrors' => [
                    [
                        'title' => 'range constraint',
                        'detail' => 'This value should be between -2147483648 and 2147483647.',
                        'source' => ['pointer' => '/data/attributes/sortOrder']
                    ]
                ]
            ],
            'kitItem null' => [
                'parameters' => [
                    'relationships' => [
                        'kitItem' => ['data' => null]
                    ]
                ],
                'expectedErrors' => [
                    [
                        'title' => 'not null constraint',
                        'detail' => 'This value should not be null.',
                        'source' => ['pointer' => '/data/relationships/kitItem/data']
                    ]
                ]
            ],
            'product null' => [
                'parameters' => [
                    'relationships' => [
                        'product' => ['data' => null]
                    ]
                ],
                'expectedErrors' => [
                    [
                        'title' => 'not null constraint',
                        'detail' => 'This value should not be null.',
                        'source' => ['pointer' => '/data/relationships/product/data']
                    ]
                ]
            ],
            'kitItem products does not contains product' => [
                'parameters' => [
                    'relationships' => [
                        'product' => [
                            'data' => ['type' => 'products', 'id' => '<toString(@product4->id)>']
                        ]
                    ]
                ],
                'expectedErrors' => [
                    [
                        'title' => 'at least one of constraint',
                        'detail' => 'Original selection no longer available',
                        'source' => ['pointer' => '/data/relationships/product/data']
                    ]
                ]
            ],
            'disabled product and optional kitItem' => [
                'parameters' => [
                    'relationships' => [
                        'product' => [
                            'data' => ['type' => 'products', 'id' => '<toString(@disabled_product5->id)>']
                        ]
                    ]
                ],
                'expectedErrors' => [
                    [
                        'title' => 'at least one of constraint',
                        'detail' => 'Original selection no longer available',
                        'source' => ['pointer' => '/data/relationships/product/data']
                    ],
                    [
                        'title' => 'access granted constraint',
                        'detail' => 'The "VIEW" permission is denied for the related resource.',
                        'source' => ['pointer' => '/data/relationships/product/data']
                    ]
                ]
            ],
            'disabled product and required kitItem' => [
                'parameters' => [
                    'relationships' => [
                        'product' => [
                            'data' => ['type' => 'products', 'id' => '<toString(@disabled_product5->id)>']
                        ],
                        'kitItem' => [
                            'data' => ['type' => 'productkititems', 'id' => '<toString(@product_kit1_item1->id)>']
                        ]
                    ]
                ],
                'expectedErrors' => [
                    [
                        'title' => 'at least one of constraint',
                        'detail' => 'Selection required',
                        'source' => ['pointer' => '/data/relationships/product/data']
                    ],
                    [
                        'title' => 'access granted constraint',
                        'detail' => 'The "VIEW" permission is denied for the related resource.',
                        'source' => ['pointer' => '/data/relationships/product/data']
                    ]
                ]
            ],
            'unit null' => [
                'parameters' => [
                    'relationships' => [
                        'unit' => ['data' => null]
                    ]
                ],
                'expectedErrors' => [
                    [
                        'title' => 'not null constraint',
                        'detail' => 'This value should not be null.',
                        'source' => ['pointer' => '/data/relationships/unit/data']
                    ]
                ]
            ],
            'unit when no KitItem' => [
                'parameters' => [
                    'relationships' => [
                        'unit' => [
                            'data' => ['type' => 'productunits', 'id' => 'item']
                        ],
                        'kitItem' => ['data' => null]
                    ],
                ],
                'expectedErrors' => [
                    [
                        'title' => 'not null constraint',
                        'detail' => 'This value should not be null.',
                        'source' => ['pointer' => '/data/relationships/kitItem/data']
                    ]
                ]
            ],
            'unit not equal to kitItem.productUnit' => [
                'parameters' => [
                    'relationships' => [
                        'unit' => [
                            'data' => ['type' => 'productunits', 'id' => 'each']
                        ]
                    ]
                ],
                'expectedErrors' => [
                    [
                        'status' => '400',
                        'title' => 'product kit item line item product unit available constraint',
                        'detail' => 'The selected product unit is not allowed',
                        'source' => ['pointer' => '/data/relationships/unit/data']
                    ]
                ]
            ]
        ];
    }

    public function testUpdateLineItem(): void
    {
        $shoppingList1 = $this->getReference('shopping_list1');
        $this->assertShoppingListTotal($shoppingList1, 59.15, 'USD');

        /** @var ProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getReference('product_kit_item1_line_item1');
        $kitLineItemId = $this->getReference('kit_line_item1')->getId();
        self::assertEquals($kitLineItemId, $kitItemLineItem->getLineItem()->getId());

        $kitItemLineItemId = (string)$kitItemLineItem->getId();
        $data = [
            'data' => [
                'type' => 'shoppinglistkititems',
                'id' => $kitItemLineItemId,
                'relationships' => [
                    'lineItem' => [
                        'data' => ['type' => 'shoppinglistitems', 'id' => '<toString(@kit_line_item2->id)>']
                    ]
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'shoppinglistkititems', 'id' => $kitItemLineItemId],
            $data
        );
        // LineItem should remain the same
        $data['data']['relationships']['lineItem']['data']['id'] = (string)$kitLineItemId;
        $this->assertResponseContains($data, $response);

        /** @var ProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getEntityManager()->find(ProductKitItemLineItem::class, $kitItemLineItemId);
        self::assertNotNull($kitItemLineItem);
        self::assertEquals(2, $kitItemLineItem->getQuantity());
        self::assertEquals($kitLineItemId, $kitItemLineItem->getLineItem()->getId());

        $kitItemLineItemShoppingList = $kitItemLineItem->getLineItem()->getShoppingList();
        self::assertEquals($shoppingList1->getId(), $kitItemLineItemShoppingList->getId());
        $this->assertShoppingListTotal($kitItemLineItemShoppingList, 59.15, 'USD');
    }

    public function testUpdate(): void
    {
        $shoppingList1 = $this->getReference('shopping_list1');
        $this->assertShoppingListTotal($shoppingList1, 59.15, 'USD');

        $kitItemLineItemId = (string)$this->getReference('product_kit_item1_line_item1')->getId();
        $data = [
            'data' => [
                'type' => 'shoppinglistkititems',
                'id' => $kitItemLineItemId,
                'attributes' => [
                    'quantity' => 123.45
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'shoppinglistkititems', 'id' => $kitItemLineItemId],
            $data
        );
        $this->assertResponseContains($data, $response);

        /** @var ProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getEntityManager()->find(ProductKitItemLineItem::class, $kitItemLineItemId);
        self::assertNotNull($kitItemLineItem);
        self::assertEquals(123.45, $kitItemLineItem->getQuantity());

        $kitItemLineItemShoppingList = $kitItemLineItem->getLineItem()->getShoppingList();
        self::assertEquals($shoppingList1->getId(), $kitItemLineItemShoppingList->getId());
        $this->assertShoppingListTotal($kitItemLineItemShoppingList, 303.59, 'USD');
    }

    public function testDelete(): void
    {
        /** @var ProductKitItemLineItem $kitItemLineItemReference */
        $kitItemLineItemReference = $this->getReference('product_kit_item1_line_item1');
        $kitItemLineItemId = $kitItemLineItemReference->getId();
        $lineItemId = $kitItemLineItemReference->getLineItem()->getId();
        $shoppingList1 = $this->getReference('shopping_list1');
        $this->assertShoppingListTotal($shoppingList1, 59.15, 'USD');

        $this->delete(
            ['entity' => 'shoppinglistkititems', 'id' => (string)$kitItemLineItemId]
        );

        /** @var ProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getEntityManager()->find(ProductKitItemLineItem::class, $kitItemLineItemId);
        self::assertNull($kitItemLineItem);

        /** @var LineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(LineItem::class, $lineItemId);
        self::assertNotNull($lineItem);
        self::assertCount(1, $lineItem->getKitItemLineItems());

        $lineItemShoppingList = $lineItem->getShoppingList();
        self::assertEquals($shoppingList1->getId(), $lineItemShoppingList->getId());
        $this->assertShoppingListTotal($lineItemShoppingList, 54.23, 'USD');
    }

    public function testDeleteList(): void
    {
        /** @var ProductKitItemLineItem $kitItemLineItemReference */
        $kitItemLineItemReference = $this->getReference('product_kit_item1_line_item1');
        $kitItemLineItemId = $kitItemLineItemReference->getId();
        $lineItemId = $kitItemLineItemReference->getLineItem()->getId();
        $shoppingList1 = $this->getReference('shopping_list1');
        $this->assertShoppingListTotal($shoppingList1, 59.15, 'USD');

        $this->cdelete(
            ['entity' => 'shoppinglistkititems'],
            ['filter' => ['id' => (string)$kitItemLineItemId]]
        );

        /** @var ProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getEntityManager()->find(ProductKitItemLineItem::class, $kitItemLineItemId);
        self::assertNull($kitItemLineItem);

        /** @var LineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(LineItem::class, $lineItemId);
        self::assertNotNull($lineItem);
        self::assertCount(1, $lineItem->getKitItemLineItems());

        $lineItemShoppingList = $lineItem->getShoppingList();
        self::assertEquals($shoppingList1->getId(), $lineItemShoppingList->getId());
        $this->assertShoppingListTotal($lineItemShoppingList, 54.23, 'USD');
    }

    private static function assertProductKitItemLineItem(
        ProductKitItemLineItem $kitItemLineItem,
        int $lineItemId,
        int $productKitItemId,
        int $productId,
        string $productUnitCode,
        float $quantity,
        int $sortOrder
    ): void {
        self::assertEquals($lineItemId, $kitItemLineItem->getLineItem()->getId());
        self::assertEquals($productKitItemId, $kitItemLineItem->getKitItem()->getId());
        self::assertEquals($productId, $kitItemLineItem->getProduct()->getId());
        self::assertEquals($productUnitCode, $kitItemLineItem->getProductUnitCode());
        self::assertEquals($quantity, $kitItemLineItem->getQuantity());
        self::assertEquals($sortOrder, $kitItemLineItem->getSortOrder());
    }

    private static function assertLineItem(
        LineItem $lineItem,
        int $organizationId,
        int $userId,
        int $customerUserId,
        float $quantity,
        string $productUnitCode,
        int $productId,
        int $kitItemLineItemsCount,
        ?string $notes = null
    ): void {
        self::assertEquals($organizationId, $lineItem->getOrganization()->getId());
        self::assertEquals($userId, $lineItem->getOwner()->getId());
        self::assertEquals($customerUserId, $lineItem->getCustomerUser()->getId());
        self::assertEquals($quantity, $lineItem->getQuantity());
        self::assertEquals($productUnitCode, $lineItem->getProductUnit()->getCode());
        self::assertEquals($productId, $lineItem->getProduct()->getId());
        self::assertEquals($notes, $lineItem->getNotes());
        self::assertCount($kitItemLineItemsCount, $lineItem->getKitItemLineItems());
    }

    private function assertShoppingListTotal(
        ShoppingList $shoppingList,
        float $total,
        string $currency
    ): void {
        /** @var ShoppingListTotal[] $totals */
        $totals = $this->getEntityManager()
            ->getRepository(ShoppingListTotal::class)
            ->findBy(['shoppingList' => $shoppingList]);
        self::assertCount(1, $totals);
        $totalEntity = $totals[0];
        self::assertEquals($total, $totalEntity->getSubtotal()->getAmount());
        self::assertEquals($currency, $totalEntity->getCurrency());
    }

    public function testOptions(): void
    {
        $response = $this->options(
            $this->getListRouteName(),
            ['entity' => 'shoppinglistkititems']
        );

        self::assertAllowResponseHeader($response, 'OPTIONS, GET, POST, DELETE');
    }
}
