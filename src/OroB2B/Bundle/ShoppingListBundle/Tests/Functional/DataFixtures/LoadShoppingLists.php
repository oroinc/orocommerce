<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class LoadShoppingLists extends AbstractFixture implements DependentFixtureInterface
{
    const SHOPPING_LIST_1 = 'shopping_list_1';
    const SHOPPING_LIST_2 = 'shopping_list_2';

    /**
     * {@inheritdoc}
     */
    function getDependencies()
    {
        return [
            'OroB2B\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserData'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $accountUser = $this->getAccountUser($manager);
        $lists = $this->getData();
        foreach ($lists as $listLabel) {
            $isCurrent = $listLabel === self::SHOPPING_LIST_2;
            $this->createShoppingList($manager, $accountUser, $listLabel, $isCurrent);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string        $name
     * @param AccountUser   $accountUser
     * @param bool          $isCurrent
     *
     * @return ShoppingList
     */
    protected function createShoppingList(ObjectManager $manager, AccountUser $accountUser, $name, $isCurrent = false)
    {
        $shoppingList = new ShoppingList();
        $shoppingList->setOwner($accountUser);
        $shoppingList->setOrganization($accountUser->getOrganization());
        $shoppingList->setAccountUser($accountUser);
        $shoppingList->setAccount($accountUser->getCustomer());
        $shoppingList->setLabel($name . '_label');
        $shoppingList->setNotes($name . '_notes');
        $shoppingList->setCurrent($isCurrent);

        $manager->persist($shoppingList);
        $this->addReference($name, $shoppingList);

        return $shoppingList;
    }

    /**
     * @param EntityManager $manager
     *
     * @return AccountUser
     * @throws \LogicException
     */
    protected function getAccountUser(EntityManager $manager)
    {
        $accountUser = $manager->getRepository('OroB2BCustomerBundle:AccountUser')
            ->createQueryBuilder('accountUser')
            ->orderBy('accountUser.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();

        if (!$accountUser) {
            throw new \LogicException('There are no account users in the system');
        }

        return $accountUser;
    }

    /**
     * @return array
     */
    protected function getData()
    {
        return [self::SHOPPING_LIST_1, self::SHOPPING_LIST_2];
    }
}
