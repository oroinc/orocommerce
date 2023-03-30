<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductKitData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

class LoadShoppingListProductKitLineItems extends AbstractShoppingListLineItemsFixture
{
    use UserUtilityTrait;

    public const LINE_ITEM_1 = 'shopping_list_product_kit_line_item.1';
    public const LINE_ITEM_1_KIT_ITEM_1 = 'shopping_list_product_kit_item_line_item.1';

    protected static $lineItems = [
        self::LINE_ITEM_1 => [
            'product' => LoadProductKitData::PRODUCT_KIT_1,
            'shoppingList' => LoadShoppingLists::SHOPPING_LIST_1,
            'unit' => 'product_unit.milliliter',
            'quantity' => 1,
            'kitItemLineItems' => [
                self::LINE_ITEM_1_KIT_ITEM_1 => [
                    'kitItem' => LoadProductKitData::PRODUCT_KIT_1 . '-kit-item-0',
                    'product' => LoadProductData::PRODUCT_1,
                    'quantity' => 11
                ],
            ],
        ],
    ];

    protected function createLineItem(ObjectManager $manager, array $lineItemData): LineItem
    {
        $lineItem = parent::createLineItem($manager, $lineItemData);

        foreach ($lineItemData['kitItemLineItems'] as $name => $kitItemLineItemData) {
            /** @var ProductKitItem $kitItem */
            $kitItem = $this->getReference($kitItemLineItemData['kitItem']);
            /** @var Product $product */
            $product = $this->getReference($kitItemLineItemData['product']);

            $kitItemLineItem = (new ProductKitItemLineItem())
                ->setKitItem($kitItem)
                ->setProduct($product)
                ->setQuantity($kitItemLineItemData['quantity'])
                ->setUnit($kitItem->getProductUnit())
                ->setSortOrder($kitItem->getSortOrder());

            $this->setReference($name, $kitItemLineItem);

            $lineItem->addKitItemLineItem($kitItemLineItem);
            $checksumGenerator = $this->container->get('oro_shopping_list.product_kit.checksum');
            $lineItem->setChecksum($checksumGenerator->getChecksum($lineItem));
        }

        return $lineItem;
    }

    public function getDependencies(): array
    {
        return [
            LoadProductUnitPrecisions::class,
            LoadShoppingLists::class,
            LoadProductKitData::class,
        ];
    }
}
