<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData as OroLoadAccountUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

/**
 * @dbIsolation
 */
class LineItemRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems',
            ]
        );
    }

    public function testFindDuplicate()
    {
        /** @var LineItem $lineItem */
        $lineItem = $this->getReference('shopping_list_line_item.1');
        $repository = $this->getLineItemRepository();

        $duplicate = $repository->findDuplicate($lineItem);
        $this->assertNull($duplicate);

        $this->setProperty($lineItem, 'id', ($lineItem->getId() + 1));
        $duplicate = $repository->findDuplicate($lineItem);
        $this->assertInstanceOf('Oro\Bundle\ShoppingListBundle\Entity\LineItem', $duplicate);
    }

    public function testGetItemsByProductAndShoppingList()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);

        /** @var LineItem $lineItem */
        $lineItem = $this->getReference('shopping_list_line_item.1');

        $lineItems = $this->getLineItemRepository()->getItemsByShoppingListAndProduct($shoppingList, $product);

        $this->assertCount(1, $lineItems);
        $this->assertContains($lineItem, $lineItems);
    }


    public function testGetOneProductLineItemsWithShoppingListNames()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        /** @var AccountUser $accountUser */
        $accountUser = $this->getAccountUserRepository()->findOneBy(['username' => OroLoadAccountUserData::AUTH_USER]);

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);

        $lineItems = $this
            ->getLineItemRepository()
            ->getOneProductLineItemsWithShoppingListNames($product, $accountUser);

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

        /** @var AccountUser $accountUser */
        $accountUser = $this->getAccountUserRepository()->findOneBy(['username' => OroLoadAccountUserData::AUTH_USER]);

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);

        $lineItems = $this->getLineItemRepository()->getProductItemsWithShoppingListNames($product, $accountUser);

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

    public function testGetLastProductsGroupedByShoppingList()
    {
        $shoppingLists = [
            $this->getReference(LoadShoppingLists::SHOPPING_LIST_1),
            $this->getReference(LoadShoppingLists::SHOPPING_LIST_5)
        ];

        $productName1 = $this->getReference(LoadProductData::PRODUCT_1)->getName()->getString();
        $productName5 = $this->getReference(LoadProductData::PRODUCT_5)->getName()->getString();

        $shoppingListId1 = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1)->getId();
        $shoppingListId5 = $this->getReference(LoadShoppingLists::SHOPPING_LIST_5)->getId();

        /** @var LineItem[] $lineItems */
        $result = $this->getLineItemRepository()->getLastProductsGroupedByShoppingList($shoppingLists, 1);

        $this->assertEquals(
            [
                $shoppingListId1 => [
                    [
                        'name' => $productName1
                    ]
                ],
                $shoppingListId5 => [
                    [
                        'name' => $productName5
                    ]
                ]
            ],
            $result
        );
    }

    /**
     * @return LineItemRepository
     */
    protected function getLineItemRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroShoppingListBundle:LineItem');
    }

    /**
     * @return EntityRepository
     */
    protected function getAccountUserRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroAccountBundle:AccountUser');
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
