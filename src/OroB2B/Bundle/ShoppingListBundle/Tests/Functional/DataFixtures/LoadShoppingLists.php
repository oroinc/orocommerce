<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

use Oro\Bundle\UserBundle\Entity\User;

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
        $user = $this->container->get('doctrine')->getRepository(User::class)->findOneBy([]);
        $this->createShoppingList($manager, 'shopping_list', $user);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string        $name
     *
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
     * @param EntityManager $manager
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
            throw new \LogicException('There are no users in system');
        }

        return $user;
    }
}
