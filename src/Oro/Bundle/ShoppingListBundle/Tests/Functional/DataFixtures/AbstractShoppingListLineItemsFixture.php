<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

abstract class AbstractShoppingListLineItemsFixture extends AbstractFixture implements DependentFixtureInterface
{
    use UserUtilityTrait;

    protected static array $lineItems = [];

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
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

    protected function createLineItem(ObjectManager $manager, array $lineItemData): LineItem
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference($lineItemData['shoppingList']);

        $owner = $this->getFirstUser($manager);
        $lineItem = new LineItem();
        $lineItem->setNotes('Test Notes');
        $lineItem->setCustomerUser($shoppingList->getCustomerUser());
        $lineItem->setOrganization($shoppingList->getOrganization());
        $lineItem->setOwner($owner);
        $lineItem->setShoppingList($shoppingList);
        $lineItem->setUnit($this->getReference($lineItemData['unit']));
        $lineItem->setProduct($this->getReference($lineItemData['product']));

        if (isset($lineItemData['parentProduct'])) {
            $lineItem->setParentProduct($this->getReference($lineItemData['parentProduct']));
        }

        if (isset($lineItemData['quantity'])) {
            $lineItem->setQuantity($lineItemData['quantity']);
        }

        return $lineItem;
    }
}
