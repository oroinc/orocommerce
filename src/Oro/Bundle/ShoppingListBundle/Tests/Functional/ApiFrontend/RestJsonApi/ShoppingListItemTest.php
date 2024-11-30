<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;
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
        for ($i = 1; $i <= 3; $i++) {
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

    private function generateLineItemChecksum(LineItem $lineItem): string
    {
        /** @var LineItemChecksumGeneratorInterface $lineItemChecksumGenerator */
        $lineItemChecksumGenerator = self::getContainer()->get('oro_product.line_item_checksum_generator');
        $checksum = $lineItemChecksumGenerator->getChecksum($lineItem);
        self::assertNotEmpty($checksum, 'Impossible to generate the line item checksum.');

        return $checksum;
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
    ): void {
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
    ): void {
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

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'shoppinglistitems'], [], ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains('cget_line_item.yml', $response);
        self::assertEquals(6, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGetListFilteredByShoppingList(): void
    {
        $response = $this->cget(
            ['entity' => 'shoppinglistitems'],
            ['filter' => ['shoppingList' => '<toString(@shopping_list1->id)>']],
            ['HTTP_X-Include' => 'totalCount']
        );

        $this->assertResponseContains('cget_line_item_filter.yml', $response);
        self::assertEquals(3, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>']
        );

        $this->assertResponseContains('get_line_item.yml', $response);
    }

    public function testUpdate(): void
    {
        $lineItemId = $this->getReference('line_item1')->getId();
        $data = [
            'data' => [
                'type' => 'shoppinglistitems',
                'id' => (string)$lineItemId,
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
        $lineItem = $this->getEntityManager()->find(LineItem::class, $lineItemId);
        self::assertEquals(123.45, $lineItem->getQuantity());
        $this->assertShoppingListTotal($lineItem->getShoppingList(), 177.68, 'USD');
    }

    public function testTryToUpdateFloatQuantityWhenPrecisionIsZero(): void
    {
        $lineItemId = $this->getReference('line_item2')->getId();
        $data = [
            'data' => [
                'type' => 'shoppinglistitems',
                'id' => (string)$lineItemId,
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
                'title' => 'quantity unit precision constraint',
                'detail' => 'The precision for the unit "item" is not valid.',
                'source' => ['pointer' => '/data/attributes/quantity']
            ],
            $response
        );
    }

    public function testCreate(): void
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
        /** @var LineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(LineItem::class, $lineItemId);
        self::assertNotNull($lineItem);
        $responseContent = $this->updateResponseContent('create_line_item.yml', $response);
        $responseContent['data']['attributes']['checksum'] = $this->generateLineItemChecksum($lineItem);
        $this->assertResponseContains($responseContent, $response);

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

    public function testTryToCreateWithFloatQuantityWhenPrecisionIsZero(): void
    {
        $response = $this->post(
            ['entity' => 'shoppinglistitems'],
            'create_line_item_precision_zero.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'quantity unit precision constraint',
                'detail' => 'The precision for the unit "item" is not valid.',
                'source' => ['pointer' => '/data/attributes/quantity']
            ],
            $response
        );
    }

    public function testCreateTogetherWithShoppingList(): void
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
        /** @var LineItem|null $lineItem */
        $lineItem = $this->getEntityManager()->find(LineItem::class, $lineItemId);
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

    public function testCreateTogetherWithShoppingListButLineItemDoesNotContainAssociationToShoppingList(): void
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
        /** @var LineItem|null $lineItem */
        $lineItem = $this->getEntityManager()->find(LineItem::class, $lineItemId);
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

    public function testCreateForDefaultShoppingListForUserWithoutShoppingLists(): void
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

        /** @var LineItem|null $lineItem */
        $lineItem = $this->getEntityManager()->find(LineItem::class, $lineItemId);
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

    public function testTryToCreateTogetherWithShoppingListWhenShoppingListLimitExceeded(): void
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
                'title' => 'create shopping list constraint',
                'detail' => 'It is not allowed to create a new shopping list.',
                'source' => ['pointer' => '/included/0']
            ],
            $response
        );
    }

    public function testCreateForShoppingListBelongsToAnotherCustomerUser(): void
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

        /** @var LineItem|null $lineItem */
        $lineItem = $this->getEntityManager()->find(LineItem::class, $lineItemId);
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
        self::assertCount(3, $shoppingList->getLineItems());
        $this->assertShoppingListTotal($lineItem->getShoppingList(), 159.7, 'USD');
    }

    public function testDelete(): void
    {
        $lineItemId = $this->getReference('line_item1')->getId();
        $shoppingListId = $this->getReference('shopping_list1')->getId();

        $this->delete(
            ['entity' => 'shoppinglistitems', 'id' => (string)$lineItemId]
        );

        $lineItem = $this->getEntityManager()->find(LineItem::class, $lineItemId);
        self::assertTrue(null === $lineItem);
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntityManager()->find(ShoppingList::class, $shoppingListId);
        self::assertCount(2, $shoppingList->getLineItems());
        $this->assertShoppingListTotal($shoppingList, 53.0, 'USD');
    }

    public function testDeleteList(): void
    {
        $lineItemId = $this->getReference('line_item1')->getId();
        $shoppingListId = $this->getReference('shopping_list1')->getId();

        $this->cdelete(
            ['entity' => 'shoppinglistitems'],
            ['filter' => ['id' => (string)$lineItemId]]
        );

        $lineItem = $this->getEntityManager()->find(LineItem::class, $lineItemId);
        self::assertTrue(null === $lineItem);
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntityManager()->find(ShoppingList::class, $shoppingListId);
        self::assertCount(2, $shoppingList->getLineItems());
        $this->assertShoppingListTotal($shoppingList, 53.0, 'USD');
    }

    public function testTryToSetZeroQuantity(): void
    {
        $data = [
            'data' => [
                'type' => 'shoppinglistitems',
                'id' => '<toString(@line_item1->id)>',
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
                'title' => 'expression constraint',
                'detail' => 'Quantity must be greater than 0',
                'source' => ['pointer' => '/data/attributes/quantity']
            ],
            $response
        );
    }

    public function testTryToSetNegativeQuantity(): void
    {
        $data = [
            'data' => [
                'type' => 'shoppinglistitems',
                'id' => '<toString(@line_item1->id)>',
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
                'title' => 'expression constraint',
                'detail' => 'Quantity must be greater than 0',
                'source' => ['pointer' => '/data/attributes/quantity']
            ],
            $response
        );
    }

    public function testTryToSetNullQuantity(): void
    {
        $data = [
            'data' => [
                'type' => 'shoppinglistitems',
                'id' => '<toString(@line_item1->id)>',
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
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/quantity']
            ],
            $response
        );
    }

    public function testTryToSetNullUnit(): void
    {
        $data = [
            'data' => [
                'type' => 'shoppinglistitems',
                'id' => '<toString(@line_item1->id)>',
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
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/relationships/unit/data']
            ],
            $response
        );
    }

    public function testTryToSetNullProduct(): void
    {
        $data = [
            'data' => [
                'type' => 'shoppinglistitems',
                'id' => '<toString(@line_item1->id)>',
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
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/relationships/product/data']
            ],
            $response
        );
    }

    public function testTryToSetNullShoppingList(): void
    {
        $data = [
            'data' => [
                'type' => 'shoppinglistitems',
                'id' => '<toString(@line_item1->id)>',
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
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/relationships/shoppingList/data']
            ],
            $response
        );
    }

    public function testTryToMoveToAnotherShoppingListWhenTargetShoppingListHasLineItemWithSameProduct(): void
    {
        $data = [
            'data' => [
                'type' => 'shoppinglistitems',
                'id' => '<toString(@line_item1->id)>',
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
                'title' => 'line item constraint',
                'detail' => 'Line Item with the same product and unit already exists'
            ],
            $response
        );
    }

    public function testTryToMoveToAnotherShoppingListFromAnotherWebsite(): void
    {
        $data = [
            'data' => [
                'type' => 'shoppinglistitems',
                'id' => '<toString(@line_item2->id)>',
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

    public function testTryToCreateForShoppingListFromAnotherWebsite(): void
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

    public function testTryToCreateWithoutRequiredFields(): void
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
                    'title' => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/relationships/product/data']
                ],
                [
                    'title' => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/relationships/shoppingList/data']
                ],
                [
                    'title' => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/relationships/unit/data']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithWrongProductUnit(): void
    {
        $response = $this->post(
            ['entity' => 'shoppinglistitems'],
            'create_line_item_wrong_unit.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'product unit exists constraint',
                'detail' => 'The product unit does not exist for the product.',
                'source' => ['pointer' => '/data/relationships/unit/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithNotSellProductProductUnit(): void
    {
        $response = $this->post(
            ['entity' => 'shoppinglistitems'],
            'create_line_item_not_sell_unit.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'product unit exists constraint',
                'detail' => 'The product unit does not exist for the product.',
                'source' => ['pointer' => '/data/relationships/unit/data']
            ],
            $response
        );
    }

    public function testTryToAddDuplicatedLineItem(): void
    {
        $response = $this->post(
            ['entity' => 'shoppinglistitems'],
            'create_line_item_duplicate.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'conflict constraint',
                'detail' => 'The entity already exists.'
            ],
            $response,
            Response::HTTP_CONFLICT
        );
    }

    public function testTryToUpdateThatCausesDuplicatedLineItem(): void
    {
        $response = $this->patch(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item2->id)>'],
            'update_line_item_duplicate.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'line item constraint',
                'detail' => 'Line Item with the same product and unit already exists'
            ],
            $response
        );
    }

    public function testTryToGetSubresourceShoppingList(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>', 'association' => 'shoppingList'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToGetRelationshipShoppingList(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>', 'association' => 'shoppingList'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToUpdateRelationshipShoppingList(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>', 'association' => 'shoppingList'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToGetSubresourceParentProduct(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>', 'association' => 'parentProduct'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToGetRelationshipParentProduct(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>', 'association' => 'parentProduct'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToUpdateRelationshipParentProduct(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>', 'association' => 'parentProduct'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToGetSubresourceProduct(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>', 'association' => 'product'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToGetRelationshipProduct(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>', 'association' => 'product'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToUpdateRelationshipProduct(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>', 'association' => 'product'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToGetSubresourceUnit(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>', 'association' => 'unit'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToGetRelationshipUnit(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>', 'association' => 'unit'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryToUpdateRelationshipUnit(): void
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

    /**
     * @dataProvider getTryToCreateKitLineItemDataProvider
     */
    public function testTryToCreateKitLineItem(string $request, array $expectedErrors): void
    {
        $response = $this->post(
            ['entity' => 'shoppinglistitems'],
            $this->getRequestData($request),
            [],
            false
        );

        $this->assertResponseValidationErrors($expectedErrors, $response);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getTryToCreateKitLineItemDataProvider(): array
    {
        return [
            'no kit item line item data' => [
                'request' => 'create_kit_line_item_without_kit_item_line_items_data.yml',
                'expectedErrors' => [
                    [
                        'title' => 'not null constraint',
                        'detail' => 'This value should not be null.',
                        'source' => ['pointer' => '/included/0/relationships/product/data']
                    ],
                    [
                        'title' => 'not null constraint',
                        'detail' => 'This value should not be null.',
                        'source' => ['pointer' => '/included/0/relationships/unit/data']
                    ],
                    [
                        'title' => 'not null constraint',
                        'detail' => 'This value should not be null.',
                        'source' => ['pointer' => '/included/0/relationships/kitItem/data']
                    ]
                ]
            ],
            'quantity out of range' => [
                'request' => 'create_kit_line_item_kit_item_line_item_quantity_out_of_range.yml',
                'expectedErrors' => [
                    [
                        'title' => 'range constraint',
                        'detail' => 'The quantity should be between 0 and 5.',
                        'source' => ['pointer' => '/included/1/attributes/quantity']
                    ]
                ]
            ],
            'no kitItem' => [
                'request' => 'create_kit_line_item_kit_item_line_item_without_kit_item.yml',
                'expectedErrors' => [
                    [
                        'title' => 'not null constraint',
                        'detail' => 'This value should not be null.',
                        'source' => ['pointer' => '/included/0/relationships/kitItem/data']
                    ]
                ]
            ],
            'disabled product and optional kitItem' => [
                'request' => 'create_kit_line_item_disabled_product_and_optional_kit_item.yml',
                'expectedErrors' => [
                    [
                        'title' => 'at least one of constraint',
                        'detail' => 'Original selection no longer available',
                        'source' => ['pointer' => '/included/1/relationships/product/data']
                    ],
                    [
                        'title' => 'access granted constraint',
                        'detail' => 'The "VIEW" permission is denied for the related resource.',
                        'source' => ['pointer' => '/included/1/relationships/product/data']
                    ]
                ]
            ],
            'disabled product and required kitItem' => [
                'request' => 'create_kit_line_item_disabled_product_and_required_kit_item.yml',
                'expectedErrors' => [
                    [
                        'title' => 'at least one of constraint',
                        'detail' => 'Selection required',
                        'source' => ['pointer' => '/included/0/relationships/product/data']
                    ],
                    [
                        'title' => 'access granted constraint',
                        'detail' => 'The "VIEW" permission is denied for the related resource.',
                        'source' => ['pointer' => '/included/0/relationships/product/data']
                    ]
                ]
            ],
            'unit not equal to kitItem.productUnit' => [
                'request' => 'create_kit_line_item_unit_not_equal_to_kit_item_product_unit.yml',
                'expectedErrors' => [
                    [
                        'title' => 'product kit item line item product unit available constraint',
                        'detail' => 'The selected product unit is not allowed',
                        'source' => ['pointer' => '/included/0/relationships/unit/data']
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider getCreateKitLineItemWithoutKitItemLineItemsDataProvider
     */
    public function testCreateKitLineItemWithoutKitItemLineItems(string $request, array $expectedErrors): void
    {
        $shoppingList = $this->getReference('shopping_list1');
        self::assertCount(3, $shoppingList->getLineItems());
        $this->assertShoppingListTotal($shoppingList, 59.15, 'USD');

        $response = $this->post(
            ['entity' => 'shoppinglistitems'],
            $this->getRequestData($request),
            [],
            false
        );

        $this->assertResponseValidationErrors($expectedErrors, $response);
    }

    public function getCreateKitLineItemWithoutKitItemLineItemsDataProvider(): array
    {
        return [
            'no kitItems' => [
                'request' => 'create_kit_line_item_without_kit_items_data.yml',
                'expectedErrors' => [
                    [
                        'title' => 'product kit line item contains required kit items constraint',
                        'detail' => 'Product kit "KIT1" is missing the required kit item "Product Kit 1 Item 1"',
                        'source' => ['pointer' => '/data/relationships/kitItems/data']
                    ]
                ]
            ],
            'empty kitItems' => [
                'request' => 'create_kit_line_item_with_empty_kit_items.yml',
                'expectedErrors' => [
                    [
                        'title' => 'product kit line item contains required kit items constraint',
                        'detail' => 'Product kit "KIT1" is missing the required kit item "Product Kit 1 Item 1"',
                        'source' => ['pointer' => '/data/relationships/kitItems/data']
                    ]
                ]
            ]
        ];
    }

    public function testCreateKitLineItem(): void
    {
        $shoppingList = $this->getReference('shopping_list1');
        self::assertCount(3, $shoppingList->getLineItems());
        $this->assertShoppingListTotal($shoppingList, 59.15, 'USD');

        $response = $this->post(
            ['entity' => 'shoppinglistitems'],
            'create_kit_line_item.yml'
        );

        $lineItemId = (int)$this->getResourceId($response);
        /** @var LineItem|null $lineItem */
        $lineItem = $this->getEntityManager()->find(LineItem::class, $lineItemId);
        self::assertNotNull($lineItem);
        $shoppingList = $lineItem->getShoppingList();
        self::assertNotNull($shoppingList);
        self::assertCount(4, $shoppingList->getLineItems());

        $responseContent = $this->updateResponseContent('create_kit_line_item.yml', $response);
        /** @var ProductKitItemLineItem $kitItemLineItem */
        foreach ($lineItem->getKitItemLineItems() as $k => $kitItemLineItem) {
            $responseContent['data']['relationships']['kitItems']['data'][$k]['id'] = (string)$kitItemLineItem->getId();
        }
        $this->assertResponseContains($responseContent, $response);
        $this->assertShoppingListTotal($shoppingList, 226.95, 'USD');
    }

    public function testUpdateKitLineItem(): void
    {
        $lineItemId = $this->getReference('kit_line_item1')->getId();
        $shoppingList = $this->getReference('shopping_list1');
        self::assertCount(3, $shoppingList->getLineItems());
        $this->assertShoppingListTotal($shoppingList, 59.15, 'USD');
        /** @var LineItem $kitLineItem */
        $kitLineItem = $this->getReference('kit_line_item1');
        self::assertCount(2, $kitLineItem->getKitItemLineItems());

        $response = $this->patch(
            ['entity' => 'shoppinglistitems', 'id' => (string)$lineItemId],
            'update_kit_line_item.yml',
        );

        /** @var LineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(LineItem::class, $lineItemId);
        self::assertCount(2, $lineItem->getKitItemLineItems());

        $shoppingList = $lineItem->getShoppingList();
        self::assertNotNull($shoppingList);
        self::assertCount(3, $shoppingList->getLineItems());

        $responseContent = $this->updateResponseContent('update_kit_line_item.yml', $response);
        /** @var ProductKitItemLineItem $kitItemLineItem */
        foreach ($lineItem->getKitItemLineItems() as $k => $kitItemLineItem) {
            $responseContent['data']['relationships']['kitItems']['data'][$k]['id'] = (string)$kitItemLineItem->getId();
        }
        $this->assertResponseContains($responseContent, $response);
        $this->assertShoppingListTotal($shoppingList, 68.03, 'USD');
    }

    public function testUpdateKitLineItemShoppingList(): void
    {
        $lineItemId = $this->getReference('kit_line_item1')->getId();
        /** @var ShoppingList $shoppingList1 */
        $shoppingList1 = $this->getReference('shopping_list1');
        self::assertCount(3, $shoppingList1->getLineItems());
        $this->assertShoppingListTotal($shoppingList1, 59.15, 'USD');
        $shoppingList1Id = $shoppingList1->getId();

        /** @var ShoppingList $shoppingList2 */
        $shoppingList2 = $this->getReference('shopping_list2');
        self::assertCount(2, $shoppingList2->getLineItems());
        $this->assertShoppingListTotal($shoppingList2, 49.8, 'USD');
        $shoppingList2Id = $shoppingList2->getId();

        /** @var LineItem $kitLineItem */
        $kitLineItem = $this->getReference('kit_line_item1');
        self::assertEquals($shoppingList1->getId(), $kitLineItem->getShoppingList()->getId());

        $data = [
            'data' => [
                'type' => 'shoppinglistitems',
                'id' => (string)$lineItemId,
                'relationships' => [
                    'shoppingList' => [
                        'data' => ['type' => 'shoppinglists', 'id' => '<toString(@shopping_list2->id)>']
                    ]
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@kit_line_item1->id)>'],
            $data
        );

        $lineItemId = (int)$this->getResourceId($response);
        /** @var LineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(LineItem::class, $lineItemId);
        self::assertNotNull($lineItem);

        $responseContent = $this->updateResponseContent('get_kit_line_item.yml', $response);
        $responseContent['data']['attributes']['checksum'] = $this->generateLineItemChecksum($lineItem);
        $responseContent['data']['relationships']['shoppingList']['data']['id'] = (string)$shoppingList2Id;
        $this->assertResponseContains($responseContent, $response);

        self::assertEquals($shoppingList2Id, $lineItem->getShoppingList()->getId());

        // Check shopping list totals
        $response = $this->get(
            ['entity' => 'shoppinglists', 'id' => $shoppingList2Id]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'attributes' => [
                        'total' => '79.4',
                        'subTotal' => '79.4'
                    ]
                ]
            ],
            $response
        );

        $response = $this->get(
            ['entity' => 'shoppinglists', 'id' => $shoppingList1Id]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'attributes' => [
                        'total' => '29.55',
                        'subTotal' => '29.55'
                    ]
                ]
            ],
            $response
        );
    }

    public function testDeleteKitLineItem(): void
    {
        /** @var LineItem $lineItem */
        $lineItem = $this->getReference('kit_line_item1');
        $lineItemId = $lineItem->getId();
        $kitItemLineItemIds = [];
        foreach ($lineItem->getKitItemLineItems() as $kitItemLineItem) {
            $kitItemLineItemIds[] = $kitItemLineItem->getId();
        }
        $shoppingList = $this->getReference('shopping_list1');
        $shoppingListId = $shoppingList->getId();
        $this->assertShoppingListTotal($shoppingList, 59.15, 'USD');

        $this->delete(
            ['entity' => 'shoppinglistitems', 'id' => (string)$lineItemId]
        );

        $lineItem = $this->getEntityManager()->find(LineItem::class, $lineItemId);
        self::assertTrue(null === $lineItem);

        foreach ($kitItemLineItemIds as $kitItemLineItemId) {
            $kitItemLineItem = $this->getEntityManager()->find(ProductKitItemLineItem::class, $kitItemLineItemId);
            self::assertTrue(null === $kitItemLineItem);
        }

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntityManager()->find(ShoppingList::class, $shoppingListId);
        self::assertCount(2, $shoppingList->getLineItems());
        $this->assertShoppingListTotal($shoppingList, 29.55, 'USD');
    }
}
