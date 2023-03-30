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

    public function load(ObjectManager $manager)
    {
        $shoppingLists = [];
        foreach (static::$lineItems as $name => $lineItemData) {
            $lineItem = $this->createLineItem($manager, $lineItemData);
            $manager->persist($lineItem);
            $this->addReference($name, $lineItem);

            $shoppingList = $lineItem->getShoppingList();
            $shoppingLists[$shoppingList->getId()] = $shoppingList;
        }

        $shoppingListTotalManager = $this->container->get('oro_shopping_list.manager.shopping_list_total');
        foreach ($shoppingLists as $shoppingList) {
            $shoppingListTotalManager->recalculateTotals($shoppingList, false);
        }

        $manager->flush();
    }

    protected function createLineItem(
        ObjectManager $manager,
        array $lineItemData
    ): LineItem {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference($lineItemData['shoppingList']);

        /** @var ProductUnit $unit */
        $unit = $this->getReference($lineItemData['unit']);

        /** @var Product $product */
        $product = $this->getReference($lineItemData['product']);

        $owner = $this->getFirstUser($manager);
        $lineItem = (new LineItem())
            ->setNotes('Test Notes')
            ->setCustomerUser($shoppingList->getCustomerUser())
            ->setOrganization($shoppingList->getOrganization())
            ->setOwner($owner)
            ->setShoppingList($shoppingList)
            ->setUnit($unit)
            ->setProduct($product);

        if (isset($lineItemData['parentProduct'])) {
            /** @var Product $parentProduct */
            $parentProduct = $this->getReference($lineItemData['parentProduct']);
            $lineItem->setParentProduct($parentProduct);
        }

        if (isset($lineItemData['quantity'])) {
            $lineItem->setQuantity($lineItemData['quantity']);
        }

        return $lineItem;
    }
}
