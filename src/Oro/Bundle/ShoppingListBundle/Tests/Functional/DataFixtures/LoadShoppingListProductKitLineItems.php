<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductKitData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;

class LoadShoppingListProductKitLineItems extends AbstractShoppingListLineItemsFixture
{
    public const LINE_ITEM_1 = 'shopping_list_product_kit_line_item.1';
    public const LINE_ITEM_1_KIT_ITEM_1 = 'shopping_list_product_kit_item_line_item.1';

    protected static array $lineItems = [
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

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadProductUnitPrecisions::class,
            LoadShoppingLists::class,
            LoadProductKitData::class,
        ];
    }

    #[\Override]
    protected function createLineItem(ObjectManager $manager, array $lineItemData): LineItem
    {
        $lineItem = parent::createLineItem($manager, $lineItemData);

        foreach ($lineItemData['kitItemLineItems'] as $name => $kitItemLineItemData) {
            /** @var ProductKitItem $kitItem */
            $kitItem = $this->getReference($kitItemLineItemData['kitItem']);

            $kitItemLineItem = new ProductKitItemLineItem();
            $kitItemLineItem->setKitItem($kitItem);
            $kitItemLineItem->setProduct($this->getReference($kitItemLineItemData['product']));
            $kitItemLineItem->setQuantity($kitItemLineItemData['quantity']);
            $kitItemLineItem->setUnit($kitItem->getProductUnit());
            $kitItemLineItem->setSortOrder($kitItem->getSortOrder());

            $this->setReference($name, $kitItemLineItem);

            $lineItem->addKitItemLineItem($kitItemLineItem);
            $checksumGenerator = $this->container->get('oro_product.line_item_checksum_generator');
            $lineItem->setChecksum($checksumGenerator->getChecksum($lineItem));
        }

        return $lineItem;
    }
}
