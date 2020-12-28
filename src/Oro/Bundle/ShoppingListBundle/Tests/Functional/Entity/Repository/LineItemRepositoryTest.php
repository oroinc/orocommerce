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
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures(
            [
                LoadShoppingListLineItems::class,
                LoadShoppingListConfigurableLineItems::class,
            ]
        );
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
        $this->assertTrue(null === $duplicate);

        $duplicate = $repository->findDuplicateInShoppingList($lineItem3, $shoppingList5);
        $this->assertFalse(null === $duplicate);
    }

    public function testGetItemsByProductAndShoppingList()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);

        /** @var LineItem $lineItem */
        $lineItem = $this->getReference('shopping_list_line_item.1');

        $lineItems = $this->getLineItemRepository()->getItemsByShoppingListAndProducts($shoppingList, [$product]);

        $this->assertCount(1, $lineItems);
        $this->assertContains($lineItem, $lineItems);
    }

    public function testGetOneProductLineItemsWithShoppingListNames()
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

        $this->assertTrue(count($lineItems) > 1);

        $shoppingListLabelList = [];
        /** @var LineItem $lineItem */
        foreach ($lineItems as $lineItem) {
            $this->assertEquals($product->getSku(), $lineItem->getProduct()->getSku());
            $shoppingListLabelList[] = $lineItem->getShoppingList()->getLabel();
        }

        $this->assertTrue(count($shoppingListLabelList) > 1);
        $this->assertTrue(in_array($shoppingList->getLabel(), $shoppingListLabelList));
    }

    public function testGetProductItemsWithShoppingListNames()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);

        $lineItems = $this->getLineItemRepository()->getProductItemsWithShoppingListNames(
            $this->getContainer()->get('oro_security.acl_helper'),
            $product,
            $shoppingList->getCustomerUser()
        );

        $this->assertTrue(count($lineItems) > 1);

        $shoppingListLabelList = [];
        $productSkuList = [];
        /** @var LineItem $lineItem */
        foreach ($lineItems as $lineItem) {
            $productSkuList[] = $lineItem->getProduct()->getSku();
            $shoppingListLabelList[] = $lineItem->getShoppingList()->getLabel();
        }

        $this->assertTrue(count($productSkuList) > 1);
        $this->assertTrue(in_array($product->getSku(), $productSkuList));

        $this->assertTrue(count($shoppingListLabelList) > 1);
        $this->assertTrue(in_array($shoppingList->getLabel(), $shoppingListLabelList));
    }

    /**
     * @dataProvider productItemsWithShoppingListNamesDataProvider
     * @param array $productReferences
     * @param array $shoppingListReferences
     * @param string $userEmail
     * @param string $roleName
     */
    public function testGetProductItemsWithShoppingListNamesForProduct7(
        array $productReferences,
        array $shoppingListReferences,
        $userEmail,
        $roleName
    ) {
        /** @var EntityRepository $userRepository */
        $userRepository = $this->getContainer()
            ->get('doctrine')
            ->getRepository(CustomerUser::class);

        /** @var CustomerUser $customerUser */
        $customerUser = $userRepository->findOneBy(['email' => $userEmail]);

        $customerUserRoleRepository = $this->getContainer()
            ->get('doctrine')
            ->getRepository(CustomerUserRole::class);
        $role = $customerUserRoleRepository->findOneBy(['role' => $roleName]);
        $token = new UsernamePasswordOrganizationToken(
            $customerUser,
            LoadCustomerUserData::LEVEL_1_PASSWORD,
            'phpunit',
            $customerUser->getOrganization(),
            [$role]
        );

        $tokenStorage = $this->getContainer()->get('security.token_storage');
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
            $expectedProductSkuList[$product->getSku()] = $product->getSku();
        }

        $expectedShoppingListLabelList = [];
        foreach ($shoppingListReferences as $shoppingListReference) {
            $expectedShoppingListLabelList[$this->getReference($shoppingListReference)->getLabel()] =
                $this->getReference($shoppingListReference)->getLabel();
        }

        $lineItems = $this->getLineItemRepository()->getProductItemsWithShoppingListNames(
            $this->getContainer()->get('oro_security.acl_helper'),
            $products
        );

        $shoppingListLabelList = [];
        $productSkuList = [];
        /** @var LineItem $lineItem */
        foreach ($lineItems as $lineItem) {
            $productSkuList[$lineItem->getProduct()->getSku()] = $lineItem->getProduct()->getSku();
            $shoppingListLabelList[$lineItem->getShoppingList()->getLabel()] = $lineItem->getShoppingList()->getLabel();
        }

        foreach ($expectedProductSkuList as $key => $value) {
            static::assertEquals($productSkuList[$key], $value);
        }
        foreach ($expectedShoppingListLabelList as $key => $value) {
            static::assertEquals($shoppingListLabelList[$key], $value);
        }
    }

    /**
     * @return array
     */
    public function productItemsWithShoppingListNamesDataProvider()
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

    public function testGetLastProductsGroupedByShoppingList()
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

        $this->assertEquals(
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

        $this->assertFalse($this->getLineItemRepository()->hasEmptyMatrix($id));
    }

    public function testHasEmptyMatrixWithOnlyOneConfigurableProduct(): void
    {
        $id = $this->getReference(LoadShoppingLists::SHOPPING_LIST_2)->getId();

        $this->assertTrue($this->getLineItemRepository()->hasEmptyMatrix($id));
    }

    public function testHasEmptyMatrixWithConfigurableProductAndVariant(): void
    {
        $id = $this->getReference(LoadShoppingLists::SHOPPING_LIST_9)->getId();

        $this->assertFalse($this->getLineItemRepository()->hasEmptyMatrix($id));
    }

    public function testCanBeGrouped(): void
    {
        $id = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1)->getId();

        $this->assertTrue($this->getLineItemRepository()->canBeGrouped($id));
    }

    public function testCanBeGroupedWhenNoItemsToGroup(): void
    {
        $id = $this->getReference(LoadShoppingLists::SHOPPING_LIST_9)->getId();

        $this->assertFalse($this->getLineItemRepository()->canBeGrouped($id));
    }

    public function testDeleteNotAllowedLineItemsFromShoppingList(): void
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_5);
        $allowedStatuses = ['in_stock'];

        $repo = $this->getLineItemRepository();
        $deletedNumber = $repo->deleteNotAllowedLineItemsFromShoppingList($shoppingList, $allowedStatuses);
        $this->assertEquals(3, $deletedNumber);

        $actual = array_map(fn (LineItem $item) => $item->getId(), $repo->findBy(['shoppingList' => $shoppingList]));
        sort($actual);

        $expected = [
            $this->getReference(LoadShoppingListLineItems::LINE_ITEM_4)->getId(),
        ];
        sort($expected);
        $this->assertEquals($expected, $actual);
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

        $this->assertCount(2, $lineItems);
        $this->assertContains($lineItem4, $lineItems);
        $this->assertContains($lineItem5, $lineItems);
    }

    /**
     * @return LineItemRepository
     */
    protected function getLineItemRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(LineItem::class);
    }

    /**
     * @return EntityRepository
     */
    protected function getCustomerUserRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(CustomerUser::class);
    }

    /**
     * @param object $object
     * @param string $property
     * @param mixed $value
     */
    protected function setProperty($object, $property, $value)
    {
        $reflection = new \ReflectionProperty(get_class($object), $property);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);
    }
}
