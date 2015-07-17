<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class LoadShoppingListLineItems extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProducts',
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits',
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions',
            'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.bottle');
        /** @var Product $product */
        $product = $this->getReference('product.1');

        $this->createLineItem($manager, $shoppingList, $unit, $product, 'shopping_list_line_item.1');

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param ShoppingList $shoppingList
     * @param ProductUnit $unit
     * @param Product $product
     * @param string $referenceName
     */
    protected function createLineItem(
        ObjectManager $manager,
        ShoppingList $shoppingList,
        ProductUnit $unit,
        Product $product,
        $referenceName
    ) {
        $item = new LineItem();
        $item->setNotes('Test Notes');
        $item->setShoppingList($shoppingList);
        $item->setUnit($unit);
        $item->setProduct($product);
        $item->setQuantity(23.15);

        $manager->persist($item);
        $this->addReference($referenceName, $item);
    }
}
