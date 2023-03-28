<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData as OroLoadCustomerUserData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadConfigurableProductWithVariants;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListConfigurableLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListProductKitLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LineItemRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([
            LoadShoppingListProductKitLineItems::class,
            LoadShoppingListLineItems::class,
            LoadShoppingListConfigurableLineItems::class,
        ]);
    }

    public function testFindDuplicateInShoppingList(): void
    {
        /** @var LineItem $lineItem */
        $lineItem3 = $this->getReference(LoadShoppingListLineItems::LINE_ITEM_3);

        /** @var ShoppingList $shoppingList4 */
        $shoppingList4 = $this->getReference(LoadShoppingLists::SHOPPING_LIST_4);

        /** @var ShoppingList $shoppingList4 */
        $shoppingList5 = $this->getReference(LoadShoppingLists::SHOPPING_LIST_5);

        $repository = $this->getLineItemRepository();

        $duplicate = $repository->findDuplicateInShoppingList($lineItem3, $shoppingList4);
        self::assertNull($duplicate);

        $duplicate = $repository->findDuplicateInShoppingList($lineItem3, $shoppingList5);
        self::assertNotNull($duplicate);
    }

    public function testFindDuplicateInShoppingListWhenHasDuplicate(): void
    {
        /** @var LineItem $lineItem1 */
        $lineItem1 = $this->getReference(LoadShoppingListLineItems::LINE_ITEM_1);

        $sameLineItem = (new LineItem())
            ->setUnit($lineItem1->getUnit())
            ->setProduct($lineItem1->getProduct());

        self::assertEquals(
            $lineItem1,
            $this->getLineItemRepository()->findDuplicateInShoppingList($sameLineItem, $lineItem1->getShoppingList())
        );
    }

    public function testFindDuplicateInShoppingListWhenShoppingListIsNull(): void
    {
        /** @var LineItem $lineItem */
        $lineItem3 = $this->getReference(LoadShoppingListLineItems::LINE_ITEM_3);

        self::assertNull($this->getLineItemRepository()->findDuplicateInShoppingList($lineItem3, null));
    }

    public function testFindDuplicateInShoppingListForProductKitLineItemWhenNoDuplicate(): void
    {
        /** @var LineItem $lineItem1 */
        $lineItem1 = $this->getReference(LoadShoppingListProductKitLineItems::LINE_ITEM_1);

        self::assertNull(
            $this->getLineItemRepository()->findDuplicateInShoppingList($lineItem1, $lineItem1->getShoppingList())
        );
    }

    public function testFindDuplicateInShoppingListForProductKitLineItemWhenHasDuplicate(): void
    {
        /** @var LineItem $lineItem1 */
        $lineItem1 = $this->getReference(LoadShoppingListProductKitLineItems::LINE_ITEM_1);

        $sameLineItem = (new LineItem())
            ->setUnit($lineItem1->getUnit())
            ->setProduct($lineItem1->getProduct())
            ->addKitItemLineItem($lineItem1->getKitItemLineItems()[0])
            ->setChecksum($lineItem1->getChecksum());

        self::assertEquals(
            $lineItem1,
            $this->getLineItemRepository()->findDuplicateInShoppingList($sameLineItem, $lineItem1->getShoppingList())
        );
    }

    public function testGetItemsByProductAndShoppingList(): void
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);

        /** @var LineItem $lineItem */
        $lineItem = $this->getReference('shopping_list_line_item.1');

        $lineItems = $this->getLineItemRepository()->getItemsByShoppingListAndProducts($shoppingList, [$product]);

        self::assertCount(1, $lineItems);
        self::assertContains($lineItem, $lineItems);
    }

    public function testGetOneProductLineItemsWithShoppingListNames(): void
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        /** @var CustomerUser $customerUser */
        $customerUser = $this->getCustomerUserRepository()
            ->findOneBy(['username' => OroLoadCustomerUserData::AUTH_USER]);

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);

        $lineItems = $this
            ->getLineItemRepository()
            ->getOneProductLineItemsWithShoppingListNames($product, $customerUser);

        self::assertTrue(count($lineItems) > 1);

        $shoppingListLabelList = [];
        /** @var LineItem $lineItem */
        foreach ($lineItems as $lineItem) {
            self::assertEquals($product->getSku(), $lineItem->getProduct()->getSku());
            $shoppingListLabelList[] = $lineItem->getShoppingList()->getLabel();
        }

        self::assertTrue(count($shoppingListLabelList) > 1);
        self::assertContains($shoppingList->getLabel(), $shoppingListLabelList);
    }

    public function testGetProductItemsWithShoppingListNames(): void
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);

        $lineItems = $this->getLineItemRepository()->getProductItemsWithShoppingListNames(
            self::getContainer()->get('oro_security.acl_helper'),
            $product,
            $shoppingList->getCustomerUser()
        );

        self::assertTrue(count($lineItems) > 1);

        $shoppingListLabelList = [];
        $productSkuList = [];
        foreach ($lineItems as $lineItem) {
            $productSkuList[] = $lineItem->getProduct()->getSku();
            $shoppingListLabelList[] = $lineItem->getShoppingList()->getLabel();
        }

        self::assertTrue(count($productSkuList) > 1);
        self::assertContains($product->getSku(), $productSkuList);

        self::assertTrue(count($shoppingListLabelList) > 1);
        self::assertContains($shoppingList->getLabel(), $shoppingListLabelList);
    }

    /**
     * @dataProvider productItemsWithShoppingListNamesDataProvider
     */
    public function testGetProductItemsWithShoppingListNamesForProduct7(
        array $productReferences,
        array $shoppingListReferences,
        string $userEmail,
        string $roleName
    ): void {
        $customerUser = $this->getCustomerUserRepository()->findOneBy(['email' => $userEmail]);
        $role = $this->getCustomerUserRoleRepository()->findOneBy(['role' => $roleName]);
        $token = new UsernamePasswordOrganizationToken(
            $customerUser,
            LoadCustomerUserData::LEVEL_1_PASSWORD,
            'phpunit',
            $customerUser->getOrganization(),
            [$role]
        );

        $tokenStorage = self::getContainer()->get('security.token_storage');
        $tokenStorage->setToken($token);

        /** @var Product[] $products */
        $products = [
            $this->getReference(LoadProductData::PRODUCT_4),
            $this->getReference(LoadProductData::PRODUCT_7)
        ];

        $expectedProductSkuList = [];
        foreach ($productReferences as $productReference) {
            /** @var Product $product */
            $product = $this->getReference($productReference);
            $productSku = $product->getSku();
            $expectedProductSkuList[$productSku] = $productSku;
        }

        $expectedShoppingListLabelList = [];
        foreach ($shoppingListReferences as $shoppingListReference) {
            $shoppingListLabel = $this->getReference($shoppingListReference)->getLabel();
            $expectedShoppingListLabelList[$shoppingListLabel] = $shoppingListLabel;
        }

        $lineItems = $this->getLineItemRepository()->getProductItemsWithShoppingListNames(
            self::getContainer()->get('oro_security.acl_helper'),
            $products
        );

        $shoppingListLabelList = [];
        $productSkuList = [];
        foreach ($lineItems as $lineItem) {
            $lineItemProductSku = $lineItem->getProduct()->getSku();
            $productSkuList[$lineItemProductSku] = $lineItemProductSku;
            $lineItemShoppingListLabel = $lineItem->getShoppingList()->getLabel();
            $shoppingListLabelList[$lineItemShoppingListLabel] = $lineItemShoppingListLabel;
        }

        foreach ($expectedProductSkuList as $key => $value) {
            self::assertEquals($productSkuList[$key], $value);
        }
        foreach ($expectedShoppingListLabelList as $key => $value) {
            self::assertEquals($shoppingListLabelList[$key], $value);
        }
    }

    public function productItemsWithShoppingListNamesDataProvider(): array
    {
        return [
            'as frontend administrator customer user has access to all shopping lists of his/her business unit' => [
                'productReferences' => [
                    LoadProductData::PRODUCT_4,
                    LoadProductData::PRODUCT_7
                ],
                'shoppingListReferences' => [
                    LoadShoppingLists::SHOPPING_LIST_6,
                    LoadShoppingLists::SHOPPING_LIST_7
                ],
                'userEmail' => LoadCustomerUserData::LEVEL_1_EMAIL,
                'roleName' => 'ROLE_FRONTEND_ADMINISTRATOR'
            ],
            'as frontend buyer customer user has access to his/her own shopping lists only' => [
                'productReferences' => [
                    LoadProductData::PRODUCT_7
                ],
                'shoppingListReferences' => [
                    LoadShoppingLists::SHOPPING_LIST_7
                ],
                'userEmail' => LoadCustomerUserData::LEVEL_1_EMAIL,
                'roleName' => 'ROLE_FRONTEND_BUYER'
            ],
        ];
    }

    public function testGetLastProductsGroupedByShoppingList(): void
    {
        $shoppingLists = [
            $this->getReference(LoadShoppingLists::SHOPPING_LIST_1),
            $this->getReference(LoadShoppingLists::SHOPPING_LIST_5)
        ];

        $parentProductName = $this->getReference(LoadConfigurableProductWithVariants::CONFIGURABLE_SKU)
            ->getName()
            ->getString();

        $productName1 = $this->getReference(LoadProductData::PRODUCT_1)->getName()->getString();
        $productName5 = $this->getReference(LoadProductData::PRODUCT_5)->getName()->getString();
        $productName8 = $this->getReference(LoadProductData::PRODUCT_8)->getName()->getString();

        $shoppingListId1 = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1)->getId();
        $shoppingListId5 = $this->getReference(LoadShoppingLists::SHOPPING_LIST_5)->getId();

        /** @var LineItem[] $lineItems */
        $result = $this->getLineItemRepository()->getLastProductsGroupedByShoppingList($shoppingLists, 2);

        self::assertEquals(
            [
                $shoppingListId1 => [
                    [
                        'name' => $parentProductName
                    ],
                    [
                        'name' => $productName1
                    ]
                ],
                $shoppingListId5 => [
                    [
                        'name' => $productName8
                    ],
                    [
                        'name' => $productName5
                    ]
                ]
            ],
            $result
        );
    }

    public function testHasEmptyMatrixWhenOnlySimpleProducts(): void
    {
        $id = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1)->getId();

        self::assertFalse($this->getLineItemRepository()->hasEmptyMatrix($id));
    }

    public function testHasEmptyMatrixWithOnlyOneConfigurableProduct(): void
    {
        $id = $this->getReference(LoadShoppingLists::SHOPPING_LIST_2)->getId();

        self::assertTrue($this->getLineItemRepository()->hasEmptyMatrix($id));
    }

    public function testHasEmptyMatrixWithConfigurableProductAndVariant(): void
    {
        $id = $this->getReference(LoadShoppingLists::SHOPPING_LIST_9)->getId();

        self::assertFalse($this->getLineItemRepository()->hasEmptyMatrix($id));
    }

    public function testCanBeGrouped(): void
    {
        $id = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1)->getId();

        self::assertTrue($this->getLineItemRepository()->canBeGrouped($id));
    }

    public function testCanBeGroupedWhenNoItemsToGroup(): void
    {
        $id = $this->getReference(LoadShoppingLists::SHOPPING_LIST_9)->getId();

        self::assertFalse($this->getLineItemRepository()->canBeGrouped($id));
    }

    public function testDeleteNotAllowedLineItemsFromShoppingList(): void
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_5);
        $allowedStatuses = ['in_stock'];

        $repo = $this->getLineItemRepository();
        $deletedNumber = $repo->deleteNotAllowedLineItemsFromShoppingList($shoppingList, $allowedStatuses);
        self::assertEquals(3, $deletedNumber);

        $actual = array_map(
            static fn (LineItem $item) => $item->getId(),
            $repo->findBy(['shoppingList' => $shoppingList])
        );
        sort($actual);

        $expected = [
            $this->getReference(LoadShoppingListLineItems::LINE_ITEM_4)->getId(),
        ];
        sort($expected);
        self::assertEquals($expected, $actual);
    }

    public function testFindLineItemsByParentProductAndUnit(): void
    {
        /** @var LineItem $lineItem */
        $lineItem4 = $this->getReference('shopping_list_configurable_line_item.4');
        $lineItem5 = $this->getReference('shopping_list_configurable_line_item.5');

        $lineItems = $this->getLineItemRepository()->findLineItemsByParentProductAndUnit(
            $lineItem4->getShoppingList()->getId(),
            $lineItem4->getParentProduct()->getId(),
            $lineItem4->getProductUnitCode()
        );

        self::assertCount(2, $lineItems);
        self::assertContains($lineItem4, $lineItems);
        self::assertContains($lineItem5, $lineItems);
    }

    private function getLineItemRepository(): LineItemRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(LineItem::class);
    }

    private function getCustomerUserRepository(): EntityRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(CustomerUser::class);
    }

    private function getCustomerUserRoleRepository(): EntityRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(CustomerUserRole::class);
    }
}
