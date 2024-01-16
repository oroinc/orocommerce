<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerVisitors;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class LoadGuestShoppingLists extends AbstractFixture implements DependentFixtureInterface
{
    public const GUEST_SHOPPING_LIST_1 = 'guest_shopping_list_1';
    public const GUEST_SHOPPING_LIST_2 = 'guest_shopping_list_2';

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [LoadCustomerVisitors::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $this->createShoppingList(
            $manager,
            $this->getReference(LoadCustomerVisitors::CUSTOMER_VISITOR),
            self::GUEST_SHOPPING_LIST_1
        );
        $this->createShoppingList(
            $manager,
            $this->getReference(LoadCustomerVisitors::CUSTOMER_VISITOR_EXPIRED),
            self::GUEST_SHOPPING_LIST_2
        );
        $manager->flush();
    }

    private function createShoppingList(
        ObjectManager $manager,
        CustomerVisitor $anonymous,
        string $reference
    ): void {
        $website = $this->getDefaultWebsite($manager);

        $shoppingList = new ShoppingList();
        $shoppingList->setOrganization($website->getOrganization());
        $shoppingList->setLabel($reference . '_label');
        $shoppingList->setNotes($reference . '_notes');
        $shoppingList->setWebsite($website);

        $manager->persist($shoppingList);

        $this->addReference($reference, $shoppingList);

        $anonymous->addShoppingList($shoppingList);
    }

    private function getDefaultWebsite(ObjectManager $manager): Website
    {
        return $manager->getRepository(Website::class)->getDefaultWebsite();
    }
}
