<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadShoppingLists extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createShoppingList($manager, 'shopping_list');

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string        $name
     *
     * @return ShoppingList
     */
    protected function createShoppingList(ObjectManager $manager, $name)
    {
        $user = $this->getReference('simple_user');

        $shoppingList = new ShoppingList();
        $shoppingList->setOwner($user);
        $shoppingList->setOrganization($user->getOrganization());
        $shoppingList->setLabel($name . '_label');
        $shoppingList->setNotes($name . '_notes');

        $manager->persist($shoppingList);
        $this->addReference($name, $shoppingList);

        return $shoppingList;
    }
}
