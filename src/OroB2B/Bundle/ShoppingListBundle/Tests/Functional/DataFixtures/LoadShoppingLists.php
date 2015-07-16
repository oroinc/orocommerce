<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class LoadShoppingLists extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $user = $this->getUser($manager);
        $this->createShoppingList($manager, 'shopping_list', $user);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $name
     * @param User $user

     * @return ShoppingList
     */
    protected function createShoppingList(ObjectManager $manager, $name, User $user)
    {
        $shoppingList = new ShoppingList();
        $shoppingList->setOwner($user);
        $shoppingList->setOrganization($user->getOrganization());
        $shoppingList->setLabel($name . '_label');
        $shoppingList->setNotes($name . '_notes');

        $manager->persist($shoppingList);
        $this->addReference($name, $shoppingList);

        return $shoppingList;
    }

    /**
     * @param ObjectManager $manager
     *
     * @return User
     * @throws \LogicException
     */
    protected function getUser(ObjectManager $manager)
    {
        $user = $manager->getRepository('OroUserBundle:User')
            ->createQueryBuilder('user')
            ->orderBy('user.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();

        if (!$user) {
            throw new \LogicException('There are no users in system');
        }

        return $user;
    }
}
