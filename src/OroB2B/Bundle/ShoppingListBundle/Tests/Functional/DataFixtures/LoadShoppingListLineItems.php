<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class LoadShoppingListLineItems extends AbstractFixture implements DependentFixtureInterface
{
    const LINE_ITEM_1 = 'shopping_list_line_item.1';
    const LINE_ITEM_2 = 'shopping_list_line_item.2';
    const LINE_ITEM_3 = 'shopping_list_line_item.3';
    const LINE_ITEM_4 = 'shopping_list_line_item.4';
    const LINE_ITEM_5 = 'shopping_list_line_item.5';

    /** @var array */
    protected static $lineItems = [
        self::LINE_ITEM_1 => [
            'product' => LoadProductData::PRODUCT_1,
            'shoppingList' => LoadShoppingLists::SHOPPING_LIST_1,
            'unit' => 'product_unit.bottle',
            'quantity' => 23.15
        ],
        self::LINE_ITEM_2 => [
            'product' => LoadProductData::PRODUCT_4,
            'shoppingList' => LoadShoppingLists::SHOPPING_LIST_3,
            'unit' => 'product_unit.bottle',
            'quantity' => 5
        ],
        self::LINE_ITEM_3 => [
            'product' => LoadProductData::PRODUCT_5,
            'shoppingList' => LoadShoppingLists::SHOPPING_LIST_4,
            'unit' => 'product_unit.bottle',
            'quantity' => 1
        ],
        self::LINE_ITEM_4 => [
            'product' => LoadProductData::PRODUCT_1,
            'shoppingList' => LoadShoppingLists::SHOPPING_LIST_5,
            'unit' => 'product_unit.box',
            'quantity' => 1
        ],
        self::LINE_ITEM_5 => [
            'product' => LoadProductData::PRODUCT_5,
            'shoppingList' => LoadShoppingLists::SHOPPING_LIST_5,
            'unit' => 'product_unit.bottle',
            'quantity' => 1
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions',
            'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (static::$lineItems as $name => $lineItem) {
            /** @var ShoppingList $shoppingList */
            $shoppingList = $this->getReference($lineItem['shoppingList']);

            /** @var ProductUnit $unit */
            $unit = $this->getReference($lineItem['unit']);

            /** @var Product $product */
            $product = $this->getReference($lineItem['product']);

            $this->createLineItem($manager, $shoppingList, $unit, $product, $lineItem['quantity'], $name);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param ShoppingList $shoppingList
     * @param ProductUnit $unit
     * @param Product $product
     * @param float $quantity
     * @param string $referenceName
     */
    protected function createLineItem(
        ObjectManager $manager,
        ShoppingList $shoppingList,
        ProductUnit $unit,
        Product $product,
        $quantity,
        $referenceName
    ) {
        $item = new LineItem();
        $item->setNotes('Test Notes')
            ->setAccountUser($shoppingList->getAccountUser())
            ->setOrganization($shoppingList->getOrganization())
            ->setShoppingList($shoppingList)
            ->setUnit($unit)
            ->setProduct($product)
            ->setQuantity($quantity);

        $manager->persist($item);
        $this->addReference($referenceName, $item);
    }
}
