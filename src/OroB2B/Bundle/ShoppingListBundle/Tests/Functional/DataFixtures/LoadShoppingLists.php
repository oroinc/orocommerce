<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class LoadShoppingLists extends AbstractFixture
{
    const SHOPPING_LIST_1 = 'shopping_list_1';
    const SHOPPING_LIST_2 = 'shopping_list_2';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $user = $this->getUser($manager);
        $accountUser = $this->getAccountUser($manager);
        foreach ($this->getData() as $shoppingListLabel) {
            $setCurrent = $shoppingListLabel === self::SHOPPING_LIST_2;
            $this->createShoppingList($manager, $shoppingListLabel, $user, $accountUser, $setCurrent);
        }

        $manager->flush();
        die();
    }

    /**
     * @param ObjectManager $manager
     * @param string        $name
     * @param User          $user
     * @param AccountUser   $accountUser
     * @param bool          $setCurrent
     *
     * @return ShoppingList
     */
    protected function createShoppingList(
        ObjectManager $manager,
        $name,
        User $user,
        AccountUser $accountUser,
        $setCurrent = false
    ) {
        $shoppingList = new ShoppingList();
        $shoppingList->setOwner($user);
        $shoppingList->setOrganization($accountUser->getOrganization());
        $shoppingList->setAccountUser($accountUser);
        $shoppingList->setAccount($accountUser->getCustomer());
        $shoppingList->setLabel($name . '_label');
        $shoppingList->setNotes($name . '_notes');
        $shoppingList->setIsCurrent($setCurrent);

        $manager->persist($shoppingList);
        $this->addReference($name, $shoppingList);

        return $shoppingList;
    }

    /**
     * @param EntityManager $manager
     *
     * @return User
     * @throws \LogicException
     */
    protected function getUser(EntityManager $manager)
    {
        $user = $manager->getRepository('OroUserBundle:User')
            ->createQueryBuilder('user')
            ->orderBy('user.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();

        if (!$user) {
            throw new \LogicException('There are no users in the system');
        }

        return $user;
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
