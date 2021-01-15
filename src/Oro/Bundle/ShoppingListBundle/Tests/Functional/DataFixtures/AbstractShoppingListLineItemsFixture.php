<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

abstract class AbstractShoppingListLineItemsFixture extends AbstractFixture implements DependentFixtureInterface
{
    use UserUtilityTrait;

    /** @var array */
    protected static $lineItems = [];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $shoppingLists = [];
        foreach (static::$lineItems as $name => $lineItem) {
            /** @var ShoppingList $shoppingList */
            $shoppingList = $this->getReference($lineItem['shoppingList']);
            $shoppingLists[$shoppingList->getId()] = $shoppingList;

            /** @var ProductUnit $unit */
            $unit = $this->getReference($lineItem['unit']);

            /** @var Product $product */
            $product = $this->getReference($lineItem['product']);

            /** @var Product $product */
            $parentProduct = null;
            if (isset($lineItem['parentProduct'])) {
                $parentProduct = $this->getReference($lineItem['parentProduct']);
            }

            $this->createLineItem(
                $manager,
                $shoppingList,
                $unit,
                $product,
                $lineItem['quantity'],
                $name,
                $parentProduct
            );
        }

        $shoppingListTotalManager = $this->container->get('oro_shopping_list.manager.shopping_list_total');
        foreach ($shoppingLists as $shoppingList) {
            $shoppingListTotalManager->recalculateTotals($shoppingList, false);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param ShoppingList $shoppingList
     * @param ProductUnit $unit
     * @param Product $product
     * @param float|null $quantity
     * @param string $referenceName
     * @param Product $parentProduct
     */
    protected function createLineItem(
        ObjectManager $manager,
        ShoppingList $shoppingList,
        ProductUnit $unit,
        Product $product,
        $quantity,
        $referenceName,
        Product $parentProduct = null
    ) {
        $owner = $this->getFirstUser($manager);
        $item = new LineItem();
        $item->setNotes('Test Notes')
            ->setCustomerUser($shoppingList->getCustomerUser())
            ->setOrganization($shoppingList->getOrganization())
            ->setOwner($owner)
            ->setShoppingList($shoppingList)
            ->setUnit($unit)
            ->setProduct($product);

        if ($quantity !== null) {
            $item->setQuantity($quantity);
        }

        if ($parentProduct) {
            $item->setParentProduct($parentProduct);
        }

        $manager->persist($item);
        $this->addReference($referenceName, $item);
    }
}
