<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Manager;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductKitData;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Bundle\ShoppingListBundle\ProductKit\Factory\ProductKitLineItemFactory;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListProductKitLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ShoppingListManagerTest extends WebTestCase
{
    private ShoppingListManager $shoppingListManager;
    private ProductKitLineItemFactory $productKitLineItemFactory;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->shoppingListManager = self::getContainer()->get('oro_shopping_list.manager.shopping_list');
        $this->productKitLineItemFactory = self::getContainer()
            ->get('oro_shopping_list.product_kit.factory.product_kit_line_item');

        $this->loadFixtures([
            LoadShoppingListProductKitLineItems::class,
        ]);
    }

    public function testAddLineItemForProductKit(): void
    {
        /** @var Product $productKit */
        $productKit = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $lineItem = $this->productKitLineItemFactory->createProductKitLineItem($productKit);

        self::assertCount(1, $shoppingList->getLineItems());

        $this->shoppingListManager->addLineItem($lineItem, $shoppingList);

        self::assertCount(2, $shoppingList->getLineItems());
    }

    public function testAddLineItemForProductKitWhenLineItemIsDuplicate(): void
    {
        /** @var LineItem $lineItem */
        $lineItem = $this->getReference(LoadShoppingListProductKitLineItems::LINE_ITEM_1);
        $sameLineItem = $this->productKitLineItemFactory->createProductKitLineItem(
            $lineItem->getProduct(),
            $lineItem->getProductUnit(),
            1
        );
        $sameKitItemLineItem = $sameLineItem->getKitItemLineItems()[0];
        $kitItemLineItem1 = $lineItem->getKitItemLineItems()[0];
        $sameKitItemLineItem->setQuantity($kitItemLineItem1->getQuantity());
        $shoppingList = $lineItem->getShoppingList();

        self::assertCount(1, $shoppingList->getLineItems());
        self::assertEquals(1, $lineItem->getQuantity());

        $this->shoppingListManager->addLineItem($sameLineItem, $shoppingList);

        self::assertCount(1, $shoppingList->getLineItems());
        self::assertEquals(2, $lineItem->getQuantity());
    }

    public function testUpdateLineItemForProductKit(): void
    {
        /** @var LineItem $lineItem */
        $lineItem = $this->getReference(LoadShoppingListProductKitLineItems::LINE_ITEM_1);
        $shoppingList = $lineItem->getShoppingList();

        self::assertCount(1, $shoppingList->getLineItems());
        self::assertEquals(1, $lineItem->getQuantity());
        self::assertEquals(11, $lineItem->getKitItemLineItems()[0]?->getQuantity());

        $kitItemLineItem1 = $lineItem->getKitItemLineItems()[0];
        $kitItemLineItem1->setQuantity(12);
        $lineItem->setQuantity(2);

        $previousChecksum = $lineItem->getChecksum();

        $this->shoppingListManager->updateLineItem($lineItem, $shoppingList);

        self::assertCount(1, $shoppingList->getLineItems());
        self::assertEquals(2, $lineItem->getQuantity());
        self::assertEquals(12, $lineItem->getKitItemLineItems()[0]?->getQuantity());
        self::assertNotEquals($previousChecksum, $lineItem->getChecksum());
    }

    public function testUpdateLineItemForProductKitWhenLineItemBecomesDuplicate(): void
    {
        /** @var LineItem $lineItem */
        $lineItem = $this->getReference(LoadShoppingListProductKitLineItems::LINE_ITEM_1);
        $shoppingList = $lineItem->getShoppingList();

        self::assertCount(1, $shoppingList->getLineItems());
        self::assertEquals(1, $lineItem->getQuantity());
        self::assertEquals(11, $lineItem->getKitItemLineItems()[0]?->getQuantity());

        $previousChecksum = $lineItem->getChecksum();

        $newLineItem = $this->productKitLineItemFactory->createProductKitLineItem($lineItem->getProduct());
        $this->shoppingListManager->addLineItem($newLineItem, $shoppingList);

        $newKitItemLineItem = $newLineItem->getKitItemLineItems()[0];
        $kitItemLineItem1 = $lineItem->getKitItemLineItems()[0];
        $newKitItemLineItem->setQuantity($kitItemLineItem1->getQuantity());

        $this->shoppingListManager->updateLineItem($newLineItem, $shoppingList);

        self::assertCount(1, $shoppingList->getLineItems());
        self::assertEquals(1, $lineItem->getQuantity());
        self::assertEquals($previousChecksum, $lineItem->getChecksum());
    }

    public function testRemoveLineItemForProductKit(): void
    {
        /** @var LineItem $lineItem */
        $lineItem = $this->getReference(LoadShoppingListProductKitLineItems::LINE_ITEM_1);
        $shoppingList = $lineItem->getShoppingList();

        self::assertCount(1, $shoppingList->getLineItems());

        $newLineItem = $this->productKitLineItemFactory->createProductKitLineItem($lineItem->getProduct());
        $this->shoppingListManager->addLineItem($newLineItem, $shoppingList);

        self::assertCount(2, $shoppingList->getLineItems());

        $this->shoppingListManager->removeLineItem($lineItem, true);

        self::assertCount(1, $shoppingList->getLineItems(), '$newLineItem was not expected to be removed');
    }

    public function testRemoveLineItemForProductKitWhenThereAreMultipleProductKitLineItems(): void
    {
        /** @var LineItem $lineItem */
        $lineItem = $this->getReference(LoadShoppingListProductKitLineItems::LINE_ITEM_1);
        $shoppingList = $lineItem->getShoppingList();

        self::assertCount(1, $shoppingList->getLineItems());

        $newLineItem = $this->productKitLineItemFactory->createProductKitLineItem($lineItem->getProduct());
        $this->shoppingListManager->addLineItem($newLineItem, $shoppingList);

        self::assertCount(2, $shoppingList->getLineItems());

        $this->shoppingListManager->removeLineItem($lineItem, false);

        self::assertCount(
            0,
            $shoppingList->getLineItems(),
            'Both $lineItem and $newLineItem were expected to be removed'
        );
    }
}
