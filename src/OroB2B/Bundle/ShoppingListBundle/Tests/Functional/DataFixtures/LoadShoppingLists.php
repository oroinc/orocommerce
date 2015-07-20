<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Component\Testing\Fixtures\LoadAccountUserData;
use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class LoadShoppingLists extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Component\Testing\Fixtures\LoadAccountUserData'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $accountUser = $this->getAccountUser($manager);
        $this->createShoppingList($manager, $accountUser, 'shopping_list');

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string        $name
     * @param AccountUser   $accountUser
     *
     * @return ShoppingList
     */
    protected function createShoppingList(ObjectManager $manager, AccountUser $accountUser, $name)
    {
        $shoppingList = new ShoppingList();
        $shoppingList->setOwner($accountUser);
        $shoppingList->setOrganization($accountUser->getOrganization());
        $shoppingList->setAccountUser($accountUser);
        $shoppingList->setAccount($accountUser->getCustomer());
        $shoppingList->setLabel($name . '_label');
        $shoppingList->setNotes($name . '_notes');

        $manager->persist($shoppingList);
        $this->addReference($name, $shoppingList);

        return $shoppingList;
    }

    /**
     * @param ObjectManager $manager
     *
     * @return AccountUser
     * @throws \LogicException
     */
    protected function getAccountUser(ObjectManager $manager)
    {
        $accountUser = $manager->getRepository('OroB2BCustomerBundle:AccountUser')
            ->findOneBy(['username' => LoadAccountUserData::AUTH_USER]);

        if (!$accountUser) {
            throw new \LogicException('Test account user not loaded');
        }

        return $accountUser;
    }
}
