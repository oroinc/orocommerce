<?php

namespace OroB2B\Bundle\ShoppingListBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\UserBundle\Entity\User;
use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class LoadShoppingListDemoData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    /** @var ContainerInterface */
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
    public function getDependencies()
    {
        return [
            'Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData',
            'OroB2B\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadAccountUserDemoData'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $accountUser = $manager->getRepository('OroB2BCustomerBundle:AccountUser')->findOneBy([]);

        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroB2BShoppingListBundle/Migrations/Data/Demo/ORM/data/shopping_lists.csv');

        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');
        $user = null;

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            /** @var User $user */
            if (!$user instanceof User || $user->getUsername() !== $row['username']) {
                $user = $manager->getRepository('OroUserBundle:User')->findOneBy(['username' => $row['username']]);
            }

            $this->createShoppingList($manager, $user, $accountUser, $row['label']);
        }

        fclose($handler);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param AccountUser   $accountUser
     * @param User          $owner
     * @param string        $label
     *
     * @return ShoppingList
     */
    protected function createShoppingList(ObjectManager $manager, User $owner, AccountUser $accountUser, $label)
    {
        $shoppingList = new ShoppingList();
        $shoppingList->setOwner($owner);
        $shoppingList->setOrganization($owner->getOrganization());
        $shoppingList->setAccountUser($accountUser);
        $shoppingList->setAccount($accountUser->getCustomer());
        $shoppingList->setNotes('Some notes for ' . $label);
        $shoppingList->setLabel($label);

        $manager->persist($shoppingList);
    }
}
