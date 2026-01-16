<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

abstract class AbstractShoppingListLineItemsFixture extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use ContainerAwareTrait;

    protected static array $lineItems = [];

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $shoppingLists = [];
        foreach (static::$lineItems as $name => $lineItemData) {
            $lineItem = $this->createLineItem($manager, $lineItemData);
            $manager->persist($lineItem);
            $this->addReference($name, $lineItem);

            $shoppingList = $lineItem->getAssociatedList();
            $shoppingLists[$shoppingList->getId()] = $shoppingList;
        }

        $shoppingListTotalManager = $this->container->get('oro_shopping_list.manager.shopping_list_total');
        foreach ($shoppingLists as $shoppingList) {
            $shoppingListTotalManager->recalculateTotals($shoppingList, false);
        }

        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadUser::class
        ];
    }

    protected function createLineItem(ObjectManager $manager, array $lineItemData): LineItem
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = isset($lineItemData['shoppingList']) ?
            $this->getReference($lineItemData['shoppingList']) :
            null;
        $savedForLaterList = isset($lineItemData['savedForLaterList']) ?
            $this->getReference($lineItemData['savedForLaterList']) :
            null;

        $lineItem = new LineItem();
        $lineItem->setNotes('Test Notes');
        $lineItem->setOwner($this->getReference(LoadUser::USER));

        if ($shoppingList) {
            $lineItem->setShoppingList($shoppingList);
            $lineItem->setCustomerUser($shoppingList->getCustomerUser());
            $lineItem->setOrganization($shoppingList->getOrganization());
        }

        if ($savedForLaterList) {
            $lineItem->setSavedForLaterList($savedForLaterList);
            $lineItem->setCustomerUser($savedForLaterList->getCustomerUser());
            $lineItem->setOrganization($savedForLaterList->getOrganization());
        }

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
