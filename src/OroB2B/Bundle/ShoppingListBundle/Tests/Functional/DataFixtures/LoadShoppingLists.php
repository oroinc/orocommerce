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

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $user = $this->getUser($manager);
        $accountUser = $this->getAccountUser($manager);
        $this->createShoppingList($manager, 'shopping_list', $user, $accountUser);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string        $name
     * @param User          $user
     * @param AccountUser   $accountUser
     *
     * @return ShoppingList
     */
    protected function createShoppingList(ObjectManager $manager, $name, User $user, AccountUser $accountUser)
    {
        $shoppingList = new ShoppingList();
        $shoppingList->setOwner($user);
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
}
