<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerVisitors;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class LoadGuestShoppingLists extends AbstractFixture implements DependentFixtureInterface
{
    const GUEST_SHOPPING_LIST_1 = 'guest_shopping_list_1';
    const GUEST_SHOPPING_LIST_2 = 'guest_shopping_list_2';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadCustomerVisitors::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createShoppingList(
            $manager,
            $this->getCustomerVisitor(LoadCustomerVisitors::CUSTOMER_VISITOR),
            self::GUEST_SHOPPING_LIST_1
        );

        $this->createShoppingList(
            $manager,
            $this->getCustomerVisitor(LoadCustomerVisitors::CUSTOMER_VISITOR_EXPIRED),
            self::GUEST_SHOPPING_LIST_2
        );

        $manager->flush();
    }

    /**
     * @param string $reference
     *
     * @return CustomerVisitor|object
     */
    private function getCustomerVisitor($reference)
    {
        return $this->getReference($reference);
    }

    /**
     * @param ObjectManager $manager
     * @param CustomerVisitor $anonymous
     * @param string $reference
     *
     * @return ShoppingList
     */
    private function createShoppingList(ObjectManager $manager, CustomerVisitor $anonymous, $reference)
    {
        $website = $this->getDefaultWebsite($manager);

        $shoppingList = new ShoppingList();
        $shoppingList->setOrganization($website->getOrganization());
        $shoppingList->setLabel($reference . '_label');
        $shoppingList->setNotes($reference . '_notes');
        $shoppingList->setWebsite($website);

        $manager->persist($shoppingList);

        $this->addReference($reference, $shoppingList);

        $anonymous->addShoppingList($shoppingList);

        return $shoppingList;
    }

    /**
     * @param ObjectManager $manager
     * @param string $email
     *
     * @return CustomerUser
     * @throws \LogicException
     */
    protected function getCustomerUser(ObjectManager $manager, $email)
    {
        $customerUser = $manager->getRepository(CustomerUser::class)
            ->findOneBy(['email' => $email]);
        if (!$customerUser) {
            throw new \LogicException('Test customer user not loaded');
        }

        return $customerUser;
    }

    /**
     * @param ObjectManager $manager
     * @return Website
     */
    protected function getDefaultWebsite(ObjectManager $manager)
    {
        return $manager->getRepository(Website::class)->getDefaultWebsite();
    }
}
