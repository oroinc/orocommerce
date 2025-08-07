<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListStorage;
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
class ShoppingListTest extends FrontendRestJsonApiTestCase
{
    private ?int $initialShoppingListLimit;

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

        $this->initialShoppingListLimit = $this->getShoppingListLimit();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->getCurrentShoppingListStorage()->set(
            $this->getReference('customer_user')->getId(),
            null
        );
        if ($this->getShoppingListLimit() !== $this->initialShoppingListLimit) {
            $this->setShoppingListLimit($this->initialShoppingListLimit);
        }
        parent::tearDown();
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

    private function getCurrentShoppingListStorage(): CurrentShoppingListStorage
    {
        return self::getContainer()->get('oro_shopping_list.current_shopping_list_storage');
    }

    private static function assertShoppingList(
        ShoppingList $shoppingList,
        int $organizationId,
        int $userId,
        int $customerId,
        int $customerUserId,
        int $websiteId,
        ?string $notes = null
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

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    private static function assertLineItem(
        LineItem $lineItem,
        int $organizationId,
        int $userId,
        int $customerUserId,
        int $shoppingListId,
        float $quantity,
        string $productUnitCode,
        int $productId,
        ?string $notes = null,
        ?int $parentProductId = null
    ): void {
        self::assertEquals($organizationId, $lineItem->getOrganization()->getId());
        self::assertEquals($userId, $lineItem->getOwner()->getId());
        self::assertEquals($customerUserId, $lineItem->getCustomerUser()->getId());
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

    private function assertShoppingListTotal(
        ShoppingList $shoppingList,
        float $total,
        string $currency
    ): void {
        $totalEntity = $this->getShoppingListTotal($shoppingList->getId());
        self::assertEquals($total, $totalEntity->getSubtotal()->getAmount());
        self::assertEquals($currency, $totalEntity->getCurrency());
    }

    private function getShoppingListTotal(int $shoppingListId): ShoppingListTotal
    {
        /** @var ShoppingListTotal[] $totals */
        $totals = $this->getEntityManager()
            ->getRepository(ShoppingListTotal::class)
            ->findBy(['shoppingList' => $shoppingListId]);
        self::assertCount(1, $totals);

        return $totals[0];
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

    public function testGetList(): void
    {
        // clear the entity manager to be sure that "compute prices" API processors work correctly
        $this->getEntityManager()->clear();

        $response = $this->cget(['entity' => 'shoppinglists'], [], ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains('cget_shopping_list.yml', $response);
        self::assertEquals(3, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGetListWithLineItemsAndKits(): void
    {
        // clear the entity manager to be sure that "compute prices" API processors work correctly
        $this->getEntityManager()->clear();

        $response = $this->cget(
            ['entity' => 'shoppinglists'],
            [
                'include' => 'items,items.kitItems',
                'fields[shoppinglists]' => 'name,currency,total,subTotal,items',
                'fields[shoppinglistitems]' => 'currency,value,quantity,subTotal,kitItems',
                'fields[shoppinglistkititems]' => 'currency,value,quantity,subTotal'
            ],
            ['HTTP_X-Include' => 'totalCount']
        );

        $this->assertResponseContains('cget_shopping_list_with_line_items_and_kits.yml', $response);
        self::assertEquals(3, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGetListWithLineItemsAndWithoutKits(): void
    {
        // clear the entity manager to be sure that "compute prices" API processors work correctly
        $this->getEntityManager()->clear();

        $response = $this->cget(
            ['entity' => 'shoppinglists'],
            [
                'include' => 'items,items.kitItems',
                'fields[shoppinglists]' => 'name,currency,total,subTotal,items',
                'fields[shoppinglistitems]' => 'currency,value,quantity,subTotal'
            ],
            ['HTTP_X-Include' => 'totalCount']
        );

        $this->assertResponseContains('cget_shopping_list_with_line_items_and_without_kits.yml', $response);
        self::assertEquals(3, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGetListWithDefaultShoppingList(): void
    {
        $this->getCurrentShoppingListStorage()->set(
            $this->getReference('customer_user')->getId(),
            $this->getReference('shopping_list1')->getId()
        );

        $response = $this->cget(['entity' => 'shoppinglists']);

        $expectedContent = $this->getResponseData('cget_shopping_list.yml');
        $expectedContent['data'][0]['attributes']['default'] = true;
        $expectedContent['data'][2]['attributes']['default'] = false;
        $this->assertResponseContains($expectedContent, $response);
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>']
        );

        $this->assertResponseContains('get_shopping_list.yml', $response);
    }

    public function testGetWithTotalOnlyIncludingSubTotalForLineItemsAndKits(): void
    {
        $response = $this->get(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>'],
            [
                'include' => 'items,items.kitItems',
                'fields[shoppinglists]' => 'total,subTotal,items',
                'fields[shoppinglistitems]' => 'subTotal,kitItems',
                'fields[shoppinglistkititems]' => 'subTotal'
            ]
        );

        $this->assertResponseContains('get_shopping_list_with_total_only.yml', $response);
        $responseData = self::jsonToArray($response->getContent());
        self::assertCount(2, $responseData['data']['attributes']);
        foreach ($responseData['included'] as $i => $item) {
            self::assertCount(1, $item['attributes'], 'include: ' . $i);
        }
    }

    public function testGetForDefaultShoppingList(): void
    {
        $this->getCurrentShoppingListStorage()->set(
            $this->getReference('customer_user')->getId(),
            $this->getReference('shopping_list1')->getId()
        );

        $response = $this->get(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>']
        );

        $expectedContent = $this->getResponseData('get_shopping_list.yml');
        $expectedContent['data']['attributes']['default'] = true;
        $this->assertResponseContains($expectedContent, $response);
    }

    public function testTryToGetByDefaultIdentifierWhenNoDefaultShoppingList(): void
    {
        $response = $this->get(
            ['entity' => 'shoppinglists', 'id' => 'default']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'shoppinglists',
                    'id' => '<toString(@shopping_list5->id)>',
                    'attributes' => [
                        'name' => 'Shopping List 5',
                        'default' => true
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetByDefaultIdentifierWhenDefaultShoppingListExists(): void
    {
        $this->getCurrentShoppingListStorage()->set(
            $this->getReference('customer_user')->getId(),
            $this->getReference('shopping_list1')->getId()
        );

        $response = $this->get(
            ['entity' => 'shoppinglists', 'id' => 'default']
        );

        $expectedContent = $this->getResponseData('get_shopping_list.yml');
        $expectedContent['data']['attributes']['default'] = true;
        $this->assertResponseContains($expectedContent, $response);
    }

    public function testUpdate(): void
    {
        $shoppingListId = $this->getReference('shopping_list1')->getId();
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

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntityManager()->find(ShoppingList::class, $shoppingListId);
        self::assertNotNull($shoppingList);
        self::assertEquals('Updated Shopping List', $shoppingList->getLabel());
    }

    public function testCreate(): void
    {
        $userId = $this->getReference('user')->getId();
        $organizationId = $this->getReference('organization')->getId();
        $customerId = $this->getReference('customer')->getId();
        $customerUserId = $this->getReference('customer_user')->getId();
        $websiteId = $this->getReference('website')->getId();
        $productId = $this->getReference('product1')->getId();
        $productUnitCode = $this->getReference('item')->getCode();

        $response = $this->post(
            ['entity' => 'shoppinglists'],
            'create_shopping_list.yml'
        );

        $shoppingListId = (int)$this->getResourceId($response);
        $responseContent = $this->updateResponseContent('create_shopping_list.yml', $response);
        $this->assertResponseContains($responseContent, $response);

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntityManager()->find(ShoppingList::class, $shoppingListId);
        self::assertNotNull($shoppingList);
        self::assertShoppingList(
            $shoppingList,
            $organizationId,
            $userId,
            $customerId,
            $customerUserId,
            $websiteId,
            'New Shopping List Notes'
        );
        $this->assertShoppingListTotal($shoppingList, 10.1, 'USD');
        self::assertCount(1, $shoppingList->getLineItems());
        /** @var LineItem $lineItem */
        $lineItem = $shoppingList->getLineItems()->first();
        self::assertLineItem(
            $lineItem,
            $organizationId,
            $userId,
            $customerUserId,
            $shoppingListId,
            10,
            $productUnitCode,
            $productId,
            'New Line Item Notes'
        );
    }

    public function testCreateWithMinimalAssociations(): void
    {
        $userId = $this->getReference('user')->getId();
        $organizationId = $this->getReference('organization')->getId();
        $customerId = $this->getReference('customer')->getId();
        $customerUserId = $this->getReference('customer_user')->getId();
        $websiteId = $this->getReference('website')->getId();
        $productId = $this->getReference('product1')->getId();
        $productUnitCode = $this->getReference('item')->getCode();

        $response = $this->post(
            ['entity' => 'shoppinglists'],
            'create_shopping_list_min.yml'
        );

        $shoppingListId = (int)$this->getResourceId($response);
        $responseContent = $this->updateResponseContent('create_shopping_list_min.yml', $response);
        $this->assertResponseContains($responseContent, $response);

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntityManager()->find(ShoppingList::class, $shoppingListId);
        self::assertShoppingList(
            $shoppingList,
            $organizationId,
            $userId,
            $customerId,
            $customerUserId,
            $websiteId
        );
        $this->assertShoppingListTotal($shoppingList, 10.1, 'USD');
        self::assertCount(1, $shoppingList->getLineItems());
        /** @var LineItem $lineItem */
        $lineItem = $shoppingList->getLineItems()->first();
        self::assertLineItem(
            $lineItem,
            $organizationId,
            $userId,
            $customerUserId,
            $shoppingListId,
            10,
            $productUnitCode,
            $productId
        );
    }

    public function testCreateWhenLineItemsAssociatedWithShoppingListButShoppingListIsNotAssociatedWithLineItems(): void
    {
        $userId = $this->getReference('user')->getId();
        $organizationId = $this->getReference('organization')->getId();
        $customerId = $this->getReference('customer')->getId();
        $customerUserId = $this->getReference('customer_user')->getId();
        $websiteId = $this->getReference('website')->getId();
        $productId = $this->getReference('product1')->getId();
        $productUnitCode = $this->getReference('item')->getCode();

        $response = $this->post(
            ['entity' => 'shoppinglists'],
            'create_shopping_list_inverse.yml'
        );

        $shoppingListId = (int)$this->getResourceId($response);
        $responseContent = $this->updateResponseContent('create_shopping_list_inverse.yml', $response);
        $this->assertResponseContains($responseContent, $response);

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntityManager()->find(ShoppingList::class, $shoppingListId);
        self::assertNotNull($shoppingList);
        self::assertShoppingList(
            $shoppingList,
            $organizationId,
            $userId,
            $customerId,
            $customerUserId,
            $websiteId
        );
        $this->assertShoppingListTotal($shoppingList, 10.1, 'USD');
        self::assertCount(1, $shoppingList->getLineItems());
        /** @var LineItem $lineItem */
        $lineItem = $shoppingList->getLineItems()->first();
        self::assertLineItem(
            $lineItem,
            $organizationId,
            $userId,
            $customerUserId,
            $shoppingListId,
            10,
            $productUnitCode,
            $productId
        );
    }

    public function testCreateEmpty(): void
    {
        $organizationId = $this->getReference('organization')->getId();
        $userId = $this->getReference('user')->getId();
        $customerId = $this->getReference('customer')->getId();
        $customerUserId = $this->getReference('customer_user')->getId();
        $websiteId = $this->getReference('website')->getId();
        $data = [
            'data' => [
                'type' => 'shoppinglists',
                'attributes' => [
                    'name' => 'New Shopping List'
                ]
            ]
        ];

        $response = $this->post(
            ['entity' => 'shoppinglists'],
            $data
        );

        $shoppingListId = (int)$this->getResourceId($response);
        $responseContent = $this->getResponseData('create_shopping_list_empty.yml');
        $responseContent['data']['id'] = (string)$shoppingListId;
        $this->assertResponseContains($responseContent, $response);

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntityManager()->find(ShoppingList::class, $shoppingListId);
        self::assertNotNull($shoppingList);
        self::assertShoppingList(
            $shoppingList,
            $organizationId,
            $userId,
            $customerId,
            $customerUserId,
            $websiteId
        );
        $this->assertShoppingListTotal($shoppingList, 0, 'USD');
        self::assertCount(0, $shoppingList->getLineItems());
    }

    public function testTryToCreateWhenShoppingListLimitExceeded(): void
    {
        $this->setShoppingListLimit(2);

        $response = $this->post(
            ['entity' => 'shoppinglists'],
            'create_shopping_list.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'create shopping list constraint',
                'detail' => 'It is not allowed to create a new shopping list.'
            ],
            $response
        );
    }

    public function testDelete(): void
    {
        $shoppingListId = $this->getReference('shopping_list1')->getId();
        $totalId = $this->getShoppingListTotal($shoppingListId)->getId();
        $lineItem1Id = $this->getReference('line_item1')->getId();
        $lineItem2Id = $this->getReference('line_item2')->getId();

        $this->delete(
            ['entity' => 'shoppinglists', 'id' => (string)$shoppingListId]
        );

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntityManager()->find(ShoppingList::class, $shoppingListId);
        self::assertTrue(null === $shoppingList);
        $shoppingListTotal = $this->getEntityManager()->find(ShoppingListTotal::class, $totalId);
        self::assertTrue(null === $shoppingListTotal);

        $deletedLineItemIds = ['line_item1' => $lineItem1Id, 'line_item2' => $lineItem2Id];
        foreach ($deletedLineItemIds as $deletedLineItemReference => $deletedLineItemId) {
            $deletedLineItem = $this->getEntityManager()->find(LineItem::class, $deletedLineItemId);
            self::assertTrue(null === $deletedLineItem, $deletedLineItemReference);
        }
    }

    public function testDeleteList(): void
    {
        $shoppingListId = $this->getReference('shopping_list2')->getId();
        $totalId = $this->getShoppingListTotal($shoppingListId)->getId();
        $lineItem3Id = $this->getReference('line_item3')->getId();

        $this->cdelete(
            ['entity' => 'shoppinglists'],
            ['filter' => ['id' => (string)$shoppingListId]]
        );

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntityManager()->find(ShoppingList::class, $shoppingListId);
        self::assertTrue(null === $shoppingList);
        $shoppingListTotal = $this->getEntityManager()->find(ShoppingListTotal::class, $totalId);
        self::assertTrue(null === $shoppingListTotal);

        $deletedLineItem = $this->getEntityManager()->find(LineItem::class, $lineItem3Id);
        self::assertTrue(null === $deletedLineItem);
    }

    public function testUpdateListOfItems(): void
    {
        $shoppingListId = $this->getReference('shopping_list1')->getId();
        $lineItem1Id = $this->getReference('line_item1')->getId();
        $lineItem2Id = $this->getReference('line_item2')->getId();
        $data = [
            'data' => [
                'type' => 'shoppinglists',
                'id' => (string)$shoppingListId,
                'relationships' => [
                    'items' => [
                        'data' => [
                            ['type' => 'shoppinglistitems', 'id' => (string)$lineItem1Id]
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'shoppinglists', 'id' => (string)$shoppingListId],
            $data
        );

        $data['data']['attributes']['currency'] = 'USD';
        $data['data']['attributes']['total'] = '6.15';
        $data['data']['attributes']['subTotal'] = '6.15';
        $this->assertResponseContains($data, $response);

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntityManager()->find(ShoppingList::class, $shoppingListId);
        self::assertNotNull($shoppingList);
        $this->assertShoppingListTotal($shoppingList, 6.15, 'USD');
        self::assertCount(1, $shoppingList->getLineItems());
        /** @var LineItem $lineItem */
        $lineItem = $shoppingList->getLineItems()->first();
        self::assertEquals($lineItem1Id, $lineItem->getId());

        $deletedLineItem = $this->getEntityManager()->find(LineItem::class, $lineItem2Id);
        self::assertTrue(null === $deletedLineItem);
    }

    public function testTryToCreateWhenCustomerUserDoesNotBelongsToCustomer(): void
    {
        $data = $this->getRequestData('create_shopping_list.yml');
        $data['data']['relationships']['customer']['data'] = [
            'type' => 'customers',
            'id' => '<toString(@child_customer->id)>'
        ];
        $data['data']['relationships']['customerUser']['data'] = [
            'type' => 'customerusers',
            'id' => '<toString(@customer_user->id)>'
        ];
        $response = $this->post(['entity' => 'shoppinglists'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'customer owner constraint',
                'detail' => 'The customer user does not belong to the customer.'
            ],
            $response
        );
    }

    public function testCreateAndSetDefaultShoppingListWhenNoDefaultShoppingList(): void
    {
        $data = $this->getRequestData('create_shopping_list_min.yml');
        $data['data']['attributes']['default'] = true;
        $response = $this->post(
            ['entity' => 'shoppinglists'],
            $data
        );

        $shoppingListId = (int)$this->getResourceId($response);
        $responseContent = $this->updateResponseContent('create_shopping_list_min.yml', $response);
        $responseContent['data']['attributes']['default'] = true;
        $this->assertResponseContains($responseContent, $response);

        self::assertSame(
            $shoppingListId,
            $this->getCurrentShoppingListStorage()->get(
                $this->getReference('customer_user')->getId()
            )
        );
    }

    public function testCreateAndSetDefaultShoppingListWhenDefaultShoppingListExists(): void
    {
        $this->getCurrentShoppingListStorage()->set(
            $this->getReference('customer_user')->getId(),
            $this->getReference('shopping_list1')->getId()
        );

        $data = $this->getRequestData('create_shopping_list_min.yml');
        $data['data']['attributes']['default'] = true;
        $response = $this->post(
            ['entity' => 'shoppinglists'],
            $data
        );

        $shoppingListId = (int)$this->getResourceId($response);
        $responseContent = $this->updateResponseContent('create_shopping_list_min.yml', $response);
        $responseContent['data']['attributes']['default'] = true;
        $this->assertResponseContains($responseContent, $response);

        self::assertSame(
            $shoppingListId,
            $this->getCurrentShoppingListStorage()->get(
                $this->getReference('customer_user')->getId()
            )
        );
    }

    public function testCreateAndResetDefaultShoppingListWhenNoDefaultShoppingList(): void
    {
        $data = $this->getRequestData('create_shopping_list_min.yml');
        $data['data']['attributes']['default'] = false;
        $response = $this->post(
            ['entity' => 'shoppinglists'],
            $data
        );

        $responseContent = $this->updateResponseContent('create_shopping_list_min.yml', $response);
        $responseContent['data']['attributes']['default'] = true;
        $this->assertResponseContains($responseContent, $response);

        self::assertNull(
            $this->getCurrentShoppingListStorage()->get(
                $this->getReference('customer_user')->getId()
            )
        );
    }

    public function testCreateAndResetDefaultShoppingListWhenDefaultShoppingListExists(): void
    {
        $existingCurrentShoppingListId = $this->getReference('shopping_list1')->getId();
        $this->getCurrentShoppingListStorage()->set(
            $this->getReference('customer_user')->getId(),
            $existingCurrentShoppingListId
        );

        $data = $this->getRequestData('create_shopping_list_min.yml');
        $data['data']['attributes']['default'] = false;
        $response = $this->post(
            ['entity' => 'shoppinglists'],
            $data
        );

        $responseContent = $this->updateResponseContent('create_shopping_list_min.yml', $response);
        $responseContent['data']['attributes']['default'] = false;
        $this->assertResponseContains($responseContent, $response);

        self::assertSame(
            $existingCurrentShoppingListId,
            $this->getCurrentShoppingListStorage()->get(
                $this->getReference('customer_user')->getId()
            )
        );
    }

    public function testCreateLineItemTogetherWithShoppingListSetDefaultShoppingList(): void
    {
        $data = $this->getRequestData('create_line_item_with_shopping_list.yml');
        $data['included'][0]['attributes']['default'] = true;
        $response = $this->post(
            ['entity' => 'shoppinglistitems'],
            $data
        );

        $responseContent = $this->updateResponseContent('create_line_item_with_shopping_list.yml', $response);
        $responseContent['included'][0]['attributes']['default'] = true;
        $this->assertResponseContains($responseContent, $response);

        $content = self::jsonToArray($response->getContent());
        $shoppingListId = (int)$content['included'][0]['id'];
        self::assertSame(
            $shoppingListId,
            $this->getCurrentShoppingListStorage()->get(
                $this->getReference('customer_user')->getId()
            )
        );
    }

    public function testTryToUpdateCustomerAndCustomerUser(): void
    {
        $shoppingListId = $this->getReference('shopping_list1')->getId();
        $response = $this->patch(
            ['entity' => 'shoppinglists', 'id' => (string)$shoppingListId],
            [
                'data' => [
                    'type' => 'shoppinglists',
                    'id' => (string)$shoppingListId,
                    'relationships' => [
                        'customer' => [
                            'data' => ['type' => 'customers', 'id' => '<toString(@child_customer->id)>']
                        ],
                        'customerUser' => [
                            'data' => ['type' => 'customerusers', 'id' => '<toString(@nancy->id)>']
                        ]
                    ]
                ]
            ]
        );

        $customerId = $this->getReference('customer')->getId();
        $customerUserId = $this->getReference('customer_user')->getId();
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'shoppinglists',
                    'id' => (string)$shoppingListId,
                    'relationships' => [
                        'customer' => [
                            'data' => ['type' => 'customers', 'id' => (string)$customerId]
                        ],
                        'customerUser' => [
                            'data' => ['type' => 'customerusers', 'id' => (string)$customerUserId]
                        ]
                    ]
                ]
            ],
            $response
        );
        $shoppingList = $this->getEntityManager()->find(ShoppingList::class, $shoppingListId);
        self::assertEquals($customerId, $shoppingList->getCustomer()->getId());
        self::assertEquals($customerUserId, $shoppingList->getCustomerUser()->getId());
    }

    public function testUpdateAndSetDefaultShoppingListWhenNoDefaultShoppingList(): void
    {
        $shoppingListId = $this->getReference('shopping_list1')->getId();
        $data = [
            'data' => [
                'type' => 'shoppinglists',
                'id' => (string)$shoppingListId,
                'attributes' => [
                    'default' => true
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'shoppinglists', 'id' => (string)$shoppingListId],
            $data
        );

        $this->assertResponseContains($data, $response);

        self::assertSame(
            $shoppingListId,
            $this->getCurrentShoppingListStorage()->get(
                $this->getReference('customer_user')->getId()
            )
        );
    }

    public function testUpdateAndSetDefaultShoppingListWhenDefaultShoppingListExists(): void
    {
        $this->getCurrentShoppingListStorage()->set(
            $this->getReference('customer_user')->getId(),
            $this->getReference('shopping_list1')->getId()
        );

        $shoppingListId = $this->getReference('shopping_list5')->getId();
        $data = [
            'data' => [
                'type' => 'shoppinglists',
                'id' => (string)$shoppingListId,
                'attributes' => [
                    'default' => true
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'shoppinglists', 'id' => (string)$shoppingListId],
            $data
        );

        $this->assertResponseContains($data, $response);

        self::assertSame(
            $shoppingListId,
            $this->getCurrentShoppingListStorage()->get(
                $this->getReference('customer_user')->getId()
            )
        );
    }

    public function testUpdateAndResetDefaultShoppingListWhenNoDefaultShoppingList(): void
    {
        $shoppingListId = $this->getReference('shopping_list1')->getId();
        $data = [
            'data' => [
                'type' => 'shoppinglists',
                'id' => (string)$shoppingListId,
                'attributes' => [
                    'default' => false
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'shoppinglists', 'id' => (string)$shoppingListId],
            $data
        );

        $this->assertResponseContains($data, $response);

        self::assertNull(
            $this->getCurrentShoppingListStorage()->get(
                $this->getReference('customer_user')->getId()
            )
        );
    }

    public function testUpdateAndResetDefaultShoppingListWhenDefaultShoppingListExists(): void
    {
        $existingCurrentShoppingListId = $this->getReference('shopping_list1')->getId();
        $this->getCurrentShoppingListStorage()->set(
            $this->getReference('customer_user')->getId(),
            $existingCurrentShoppingListId
        );

        $shoppingListId = $this->getReference('shopping_list5')->getId();
        $data = [
            'data' => [
                'type' => 'shoppinglists',
                'id' => (string)$shoppingListId,
                'attributes' => [
                    'default' => false
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'shoppinglists', 'id' => (string)$shoppingListId],
            $data
        );

        $this->assertResponseContains($data, $response);

        self::assertSame(
            $existingCurrentShoppingListId,
            $this->getCurrentShoppingListStorage()->get(
                $this->getReference('customer_user')->getId()
            )
        );
    }

    public function testUpdateAndResetDefaultShoppingListWhenThisShoppingListIsDefault(): void
    {
        $shoppingListId = $this->getReference('shopping_list5')->getId();
        $this->getCurrentShoppingListStorage()->set(
            $this->getReference('customer_user')->getId(),
            $shoppingListId
        );

        $data = [
            'data' => [
                'type' => 'shoppinglists',
                'id' => (string)$shoppingListId,
                'attributes' => [
                    'default' => false
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'shoppinglists', 'id' => (string)$shoppingListId],
            $data
        );

        $responseContent = $data;
        $responseContent['data']['attributes']['default'] = true;
        $this->assertResponseContains($responseContent, $response);

        self::assertNull(
            $this->getCurrentShoppingListStorage()->get(
                $this->getReference('customer_user')->getId()
            )
        );
    }

    public function testTryToGetFromAnotherWebsite(): void
    {
        $response = $this->get(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list3->id)>'],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            ['title' => 'access denied exception'],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToUpdateFromAnotherWebsite(): void
    {
        $shoppingListId = $this->getReference('shopping_list3')->getId();
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
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            ['title' => 'access denied exception'],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToDeleteFromAnotherWebsite(): void
    {
        $shoppingListId = $this->getReference('shopping_list3')->getId();

        $response = $this->delete(
            ['entity' => 'shoppinglists', 'id' => (string)$shoppingListId],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            ['title' => 'access denied exception'],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToDeleteListFromAnotherWebsite(): void
    {
        $shoppingListId = $this->getReference('shopping_list3')->getId();

        $this->cdelete(
            ['entity' => 'shoppinglists'],
            ['filter' => ['id' => (string)$shoppingListId]]
        );

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntityManager()->find(ShoppingList::class, $shoppingListId);
        self::assertNotNull($shoppingList);
    }

    public function testTryToSetEmptyName(): void
    {
        $data = [
            'data' => [
                'type' => 'shoppinglists',
                'id' => '<toString(@shopping_list1->id)>',
                'attributes' => [
                    'name' => ''
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/name']
            ],
            $response
        );
    }

    public function testTryToCreateWithoutRequiredFields(): void
    {
        $response = $this->post(
            ['entity' => 'shoppinglists'],
            ['data' => ['type' => 'shoppinglists']],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/name']
            ],
            $response
        );
    }

    public function testAddToCartForNewListItem(): void
    {
        $userId = $this->getReference('user')->getId();
        $organizationId = $this->getReference('organization')->getId();
        $customerUserId = $this->getReference('customer_user')->getId();
        $productId = $this->getReference('product1')->getId();
        $productUnitCode = $this->getReference('set')->getCode();
        $shoppingListId = $this->getReference('shopping_list1')->getId();

        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => (string)$shoppingListId, 'association' => 'items'],
            'add_line_item.yml'
        );

        $responseContent = $this->updateResponseContent('add_line_item.yml', $response);
        $responseContent['data'][0]['relationships']['shoppingList'] = [
            'data' => ['type' => 'shoppinglists', 'id' => (string)$shoppingListId]
        ];
        $this->assertResponseContains($responseContent, $response);

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntityManager()->find(ShoppingList::class, $shoppingListId);
        self::assertCount(4, $shoppingList->getLineItems());
        $lineItem = $this->getLineItemById($shoppingList, (int)$responseContent['data'][0]['id']);
        self::assertLineItem(
            $lineItem,
            $organizationId,
            $userId,
            $customerUserId,
            $shoppingListId,
            10,
            $productUnitCode,
            $productId,
            'New Line Item Notes'
        );
        $this->assertShoppingListTotal($shoppingList, 169.05, 'USD');
    }

    public function testAddToCartForExistingListItem(): void
    {
        $userId = $this->getReference('user')->getId();
        $organizationId = $this->getReference('organization')->getId();
        $customerUserId = $this->getReference('customer_user')->getId();
        $productId = $this->getReference('product1')->getId();
        $productUnitCode = $this->getReference('item')->getCode();
        $shoppingListId = $this->getReference('shopping_list1')->getId();
        $lineItemId = $this->getReference('line_item1')->getId();

        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => (string)$shoppingListId, 'association' => 'items'],
            'add_line_item_existing.yml'
        );

        $responseContent = $this->getResponseData('add_line_item_existing.yml');
        $responseContent['data'][0]['relationships']['shoppingList'] = [
            'data' => ['type' => 'shoppinglists', 'id' => (string)$shoppingListId]
        ];
        $this->assertResponseContains($responseContent, $response);

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntityManager()->find(ShoppingList::class, $shoppingListId);
        self::assertCount(3, $shoppingList->getLineItems());
        $lineItem = $this->getLineItemById($shoppingList, $lineItemId);
        self::assertLineItem(
            $lineItem,
            $organizationId,
            $userId,
            $customerUserId,
            $shoppingListId,
            15,
            $productUnitCode,
            $productId,
            'Updated Existing Line Item Notes'
        );
        $this->assertShoppingListTotal($shoppingList, 68.15, 'USD');
    }

    public function testAddToCartWithShoppingListInRequestData(): void
    {
        $userId = $this->getReference('user')->getId();
        $organizationId = $this->getReference('organization')->getId();
        $customerUserId = $this->getReference('customer_user')->getId();
        $productId = $this->getReference('product1')->getId();
        $productUnitCode = $this->getReference('set')->getCode();
        $shoppingListId = $this->getReference('shopping_list1')->getId();

        $data = $this->getRequestData('add_line_item.yml');
        $data['data'][0]['relationships']['shoppingList'] = [
            'data' => ['type' => 'shoppinglists', 'id' => '<toString(@shopping_list2->id)>']
        ];
        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => (string)$shoppingListId, 'association' => 'items'],
            $data
        );

        $responseContent = $this->updateResponseContent('add_line_item.yml', $response);
        $responseContent['data'][0]['relationships']['shoppingList'] = [
            'data' => ['type' => 'shoppinglists', 'id' => (string)$shoppingListId]
        ];
        $this->assertResponseContains($responseContent, $response);

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntityManager()->find(ShoppingList::class, $shoppingListId);
        self::assertCount(4, $shoppingList->getLineItems());
        $lineItem = $this->getLineItemById($shoppingList, (int)$responseContent['data'][0]['id']);
        self::assertLineItem(
            $lineItem,
            $organizationId,
            $userId,
            $customerUserId,
            $shoppingListId,
            10,
            $productUnitCode,
            $productId,
            'New Line Item Notes'
        );
        $this->assertShoppingListTotal($shoppingList, 169.05, 'USD');
    }

    public function testAddToCartForNewListItemForDefaultShoppingList(): void
    {
        $userId = $this->getReference('user')->getId();
        $organizationId = $this->getReference('organization')->getId();
        $customerUserId = $this->getReference('customer_user')->getId();
        $productId = $this->getReference('product1')->getId();
        $productUnitCode = $this->getReference('set')->getCode();
        $shoppingListId = $this->getReference('shopping_list1')->getId();

        $this->getCurrentShoppingListStorage()->set($customerUserId, $shoppingListId);

        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => 'default', 'association' => 'items'],
            'add_line_item.yml'
        );

        $responseContent = $this->updateResponseContent('add_line_item.yml', $response);
        $this->assertResponseContains($responseContent, $response);

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntityManager()->find(ShoppingList::class, $shoppingListId);
        self::assertCount(4, $shoppingList->getLineItems());
        $lineItem = $this->getLineItemById($shoppingList, (int)$responseContent['data'][0]['id']);
        self::assertLineItem(
            $lineItem,
            $organizationId,
            $userId,
            $customerUserId,
            $shoppingListId,
            10,
            $productUnitCode,
            $productId,
            'New Line Item Notes'
        );
        $this->assertShoppingListTotal($shoppingList, 169.05, 'USD');
    }

    public function testAddToCartForNewListItemForDefaultShoppingListWhenNoDefaultShoppingList(): void
    {
        $userId = $this->getReference('user')->getId();
        $organizationId = $this->getReference('organization')->getId();
        $customerUserId = $this->getReference('customer_user')->getId();
        $productId = $this->getReference('product1')->getId();
        $productUnitCode = $this->getReference('set')->getCode();
        $shoppingListId = $this->getReference('shopping_list5')->getId();

        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => 'default', 'association' => 'items'],
            'add_line_item.yml'
        );

        $responseContent = $this->updateResponseContent('add_line_item.yml', $response);
        $this->assertResponseContains($responseContent, $response);

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntityManager()->find(ShoppingList::class, $shoppingListId);
        self::assertCount(2, $shoppingList->getLineItems());
        $lineItem = $this->getLineItemById($shoppingList, (int)$responseContent['data'][0]['id']);
        self::assertLineItem(
            $lineItem,
            $organizationId,
            $userId,
            $customerUserId,
            $shoppingListId,
            10,
            $productUnitCode,
            $productId,
            'New Line Item Notes'
        );
        $this->assertShoppingListTotal($shoppingList, 111.13, 'USD');
    }

    public function testTryToAddToCartWithId(): void
    {
        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'items'],
            'add_line_item_with_id.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'request data constraint',
                'detail' => 'The identifier should not be specified',
                'source' => ['pointer' => '/data/0/id'],
            ],
            $response
        );
    }

    public function testTryToAddToCartForNewListItemWithWrongProductUnit(): void
    {
        $shoppingListId = $this->getReference('shopping_list1')->getId();

        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => (string)$shoppingListId, 'association' => 'items'],
            'add_line_item_wrong_unit.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'product unit exists constraint',
                'detail' => 'The product unit does not exist for the product.',
                'source' => ['pointer' => '/data/0/relationships/unit/data']
            ],
            $response
        );
    }

    public function testTryToAddToCartForNewListItemNotSellProductProductUnit(): void
    {
        $shoppingListId = $this->getReference('shopping_list1')->getId();

        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => (string)$shoppingListId, 'association' => 'items'],
            'add_line_item_not_sell_unit.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'product unit exists constraint',
                'detail' => 'The product unit does not exist for the product.',
                'source' => ['pointer' => '/data/0/relationships/unit/data']
            ],
            $response
        );
    }

    public function testTryToAddToCartWithoutUnit(): void
    {
        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'items'],
            'add_line_item_no_unit.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/0/relationships/unit/data']
            ],
            $response
        );
    }

    public function testTryToAddToCartWithInvalidQuantityForNewListItem(): void
    {
        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'items'],
            'add_line_item_invalid_quantity_new.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'form constraint',
                'detail' => 'Please enter a number.',
                'source' => ['pointer' => '/data/0/attributes/quantity']
            ],
            $response
        );
    }

    public function testTryToAddToCartWithInvalidQuantityForExistingListItem(): void
    {
        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'items'],
            'add_line_item_invalid_quantity_existing.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'form constraint',
                'detail' => 'Please enter a number.',
                'source' => ['pointer' => '/data/0/attributes/quantity']
            ],
            $response
        );
    }

    public function testTryToAddToCartWithInvalidQuantityBecauseOfProductPrecisionForNewListItem(): void
    {
        $data = $this->getRequestData('add_line_item_invalid_quantity_new.yml');
        $data['data'][0]['attributes']['quantity'] = 1.2345;

        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'items'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'quantity unit precision constraint',
                'detail' => 'The precision for the unit "set" is not valid.',
                'source' => ['pointer' => '/data/0/attributes/quantity']
            ],
            $response
        );
    }

    public function testTryToAddToCartWithInvalidQuantityBecauseOfProductPrecisionForExistingListItem(): void
    {
        $data = $this->getRequestData('add_line_item_invalid_quantity_existing.yml');
        $data['data'][0]['attributes']['quantity'] = 1.2345;

        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'items'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'quantity unit precision constraint',
                'detail' => 'The precision for the unit "item" is not valid.',
                'source' => ['pointer' => '/data/0/attributes/quantity']
            ],
            $response
        );
    }

    public function testTryToAddToCartWithNegativeQuantityForNewListItem(): void
    {
        $data = $this->getRequestData('add_line_item_invalid_quantity_new.yml');
        $data['data'][0]['attributes']['quantity'] = -1;

        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'items'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'quantity to order constraint',
                    'detail' => 'You cannot order less than 0 units',
                    'source' => ['pointer' => '/data/0/attributes/quantity']
                ],
                [
                    'title' => 'expression constraint',
                    'detail' => 'Quantity must be greater than 0',
                    'source' => ['pointer' => '/data/0/attributes/quantity']
                ],
            ],
            $response
        );
    }

    public function testTryToAddToCartWithNegativeQuantityForExistingListItem(): void
    {
        $data = $this->getRequestData('add_line_item_invalid_quantity_existing.yml');
        $data['data'][0]['attributes']['quantity'] = -1;

        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'items'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'quantity to order constraint',
                    'detail' => 'You cannot order less than 0 units',
                    'source' => ['pointer' => '/data/0/attributes/quantity']
                ],
                [
                    'title' => 'expression constraint',
                    'detail' => 'Quantity must be greater than 0',
                    'source' => ['pointer' => '/data/0/attributes/quantity']
                ],
            ],
            $response
        );
    }

    public function testTryToAddToCartWithZeroQuantityForNewListItem(): void
    {
        $data = $this->getRequestData('add_line_item_invalid_quantity_new.yml');
        $data['data'][0]['attributes']['quantity'] = 0;

        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'items'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'expression constraint',
                'detail' => 'Quantity must be greater than 0',
                'source' => ['pointer' => '/data/0/attributes/quantity']
            ],
            $response
        );
    }

    public function testTryToAddToCartWithZeroQuantityForExistingListItem(): void
    {
        $data = $this->getRequestData('add_line_item_invalid_quantity_existing.yml');
        $data['data'][0]['attributes']['quantity'] = 0;

        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'items'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'expression constraint',
                'detail' => 'Quantity must be greater than 0',
                'source' => ['pointer' => '/data/0/attributes/quantity']
            ],
            $response
        );
    }

    public function testTryToAddToCartWithEmptyQuantityForNewListItem(): void
    {
        $data = $this->getRequestData('add_line_item_invalid_quantity_new.yml');
        $data['data'][0]['attributes']['quantity'] = '';

        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'items'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'form constraint',
                'detail' => 'Please enter a number.',
                'source' => ['pointer' => '/data/0/attributes/quantity']
            ],
            $response
        );
    }

    public function testTryToAddToCartWithEmptyQuantityForExistingListItem(): void
    {
        $data = $this->getRequestData('add_line_item_invalid_quantity_existing.yml');
        $data['data'][0]['attributes']['quantity'] = '';

        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'items'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'form constraint',
                'detail' => 'Please enter a number.',
                'source' => ['pointer' => '/data/0/attributes/quantity']
            ],
            $response
        );
    }

    public function testTryToAddToCartWithNullQuantityForNewListItem(): void
    {
        $data = $this->getRequestData('add_line_item_invalid_quantity_new.yml');
        $data['data'][0]['attributes']['quantity'] = null;

        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'items'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/0/attributes/quantity']
            ],
            $response
        );
    }

    public function testTryToAddToCartWithNullQuantityForExistingListItem(): void
    {
        $data = $this->getRequestData('add_line_item_invalid_quantity_existing.yml');
        $data['data'][0]['attributes']['quantity'] = null;

        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'items'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/0/attributes/quantity']
            ],
            $response
        );
    }

    public function testTryToAddToCartWithoutQuantityForNewListItem(): void
    {
        $data = $this->getRequestData('add_line_item_invalid_quantity_new.yml');
        unset($data['data'][0]['attributes']);

        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'items'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/0/attributes/quantity']
            ],
            $response
        );
    }

    public function testTryToAddToCartWithoutQuantityForExistingListItem(): void
    {
        $data = $this->getRequestData('add_line_item_invalid_quantity_existing.yml');
        unset($data['data'][0]['attributes']);

        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'items'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/0/attributes/quantity']
            ],
            $response
        );
    }

    public function testAddToCartForDefaultShoppingListForUserWithoutShoppingLists(): void
    {
        $userId = $this->getReference('user')->getId();
        $organizationId = $this->getReference('organization')->getId();
        $customerUserId = $this->getReference('john')->getId();
        $productId = $this->getReference('product1')->getId();
        $productUnitCode = $this->getReference('set')->getCode();

        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => 'default', 'association' => 'items'],
            'add_line_item.yml',
            self::generateApiAuthHeader('john@example.com')
        );

        $responseContent = $this->updateResponseContent('add_line_item.yml', $response);
        $this->assertResponseContains($responseContent, $response);

        /** @var LineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(LineItem::class, (int)$responseContent['data'][0]['id']);
        $shoppingList = $lineItem->getShoppingList();
        self::assertEquals(
            self::getContainer()->get('translator')->trans('oro.shoppinglist.default.label'),
            $shoppingList->getLabel()
        );
        self::assertCount(1, $shoppingList->getLineItems());
        self::assertLineItem(
            $lineItem,
            $organizationId,
            $userId,
            $customerUserId,
            $shoppingList->getId(),
            10,
            $productUnitCode,
            $productId,
            'New Line Item Notes'
        );
        $this->assertShoppingListTotal($shoppingList, 109.9, 'USD');
    }

    public function testGetSubresourceCustomer(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'customer']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'customers',
                    'id' => '<toString(@customer->id)>',
                    'attributes' => [
                        'name' => 'Customer'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipCustomer(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'customer']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'customers',
                    'id' => '<toString(@customer->id)>'
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateRelationshipCustomer(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'customer'],
            [],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceCustomerUser(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'customerUser']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'customerusers',
                    'id' => '<toString(@customer_user->id)>',
                    'attributes' => [
                        'email' => 'frontend_admin_api@example.com'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipCustomerUser(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'customerUser']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'customerusers',
                    'id' => '<toString(@customer_user->id)>'
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateRelationshipCustomerUser(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'customerUser'],
            [],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceItems(): void
    {
        // clear the entity manager to be sure that "compute prices" API processors work correctly
        $this->getEntityManager()->clear();

        $response = $this->getSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'items']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'shoppinglistitems',
                        'id' => '<toString(@line_item1->id)>',
                        'attributes' => [
                            'quantity' => 5,
                            'currency' => 'USD',
                            'value' => '1.2300',
                            'subTotal' => '6.1500'
                        ]
                    ],
                    [
                        'type' => 'shoppinglistitems',
                        'id' => '<toString(@line_item2->id)>',
                        'attributes' => [
                            'quantity' => 10,
                            'currency' => 'USD',
                            'value' => '2.3400',
                            'subTotal' => '23.4000'
                        ]
                    ],
                    [
                        'type' => 'shoppinglistitems',
                        'id' => '<toString(@kit_line_item1->id)>',
                        'attributes' => [
                            'quantity' => 2,
                            'currency' => 'USD',
                            'value' => '14.8000',
                            'subTotal' => '29.6000'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateSubresourceRelationshipItems(): void
    {
        $response = $this->patchSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'items'],
            [],
            [],
            false
        );

        self::assertAllowResponseHeader($response, 'OPTIONS, GET, POST');
    }

    public function testTryToDeleteSubresourceRelationshipItems(): void
    {
        $response = $this->deleteSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'items'],
            [],
            [],
            false
        );

        self::assertAllowResponseHeader($response, 'OPTIONS, GET, POST');
    }

    public function testGetRelationshipItems(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'items']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>'],
                    ['type' => 'shoppinglistitems', 'id' => '<toString(@line_item2->id)>'],
                    ['type' => 'shoppinglistitems', 'id' => '<toString(@kit_line_item1->id)>']
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateRelationshipItems(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'items'],
            [],
            [],
            false
        );

        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    public function testTryToAddRelationshipItems(): void
    {
        $response = $this->postRelationship(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'items'],
            [],
            [],
            false
        );

        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteRelationshipItems(): void
    {
        $response = $this->deleteRelationship(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'items'],
            [],
            [],
            false
        );

        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    public function testResetDefaultShoppingListAfterShoppingListDeletion(): void
    {
        $customerUserId = $this->getReference('customer_user')->getId();
        $shoppingListId = $this->getReference('shopping_list1')->getId();

        $this->getCurrentShoppingListStorage()->set($customerUserId, $shoppingListId);

        $response = $this->delete(['entity' => 'shoppinglists', 'id' => $shoppingListId]);
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NO_CONTENT);
        self::assertNull($this->getCurrentShoppingListStorage()->get($customerUserId));

        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => 'default', 'association' => 'items'],
            'add_line_item.yml'
        );

        $responseContent = $this->updateResponseContent('add_line_item.yml', $response);
        $this->assertResponseContains($responseContent, $response);
    }
}
