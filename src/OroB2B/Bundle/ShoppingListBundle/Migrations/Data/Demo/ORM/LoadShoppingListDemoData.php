<?php

namespace OroB2B\Bundle\ShoppingListBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class LoadShoppingListDemoData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    /** @var array */
    protected $websites = [];

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
            'OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM\LoadAccountUserDemoData'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $accountUser = $manager->getRepository('OroB2BAccountBundle:AccountUser')->findOneBy([]);

        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroB2BShoppingListBundle/Migrations/Data/Demo/ORM/data/shopping_lists.csv');

        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        $first = true;
        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));
            $website = $this->getWebsite($manager, $row['websiteName']);
            $this->createShoppingList($manager, $accountUser, $row['label'], $first, $website);
            $first = false;
        }

        fclose($handler);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param AccountUser   $accountUser
     * @param string        $label
     * @param boolean       $current
     * @param Website       $website
     *
     * @return ShoppingList
     */
    protected function createShoppingList(ObjectManager $manager, AccountUser $accountUser, $label, $current, $website)
    {
        $shoppingList = new ShoppingList();
        $shoppingList->setOrganization($accountUser->getOrganization());
        $shoppingList->setAccountUser($accountUser);
        $shoppingList->setAccount($accountUser->getAccount());
        $shoppingList->setNotes('Some notes for ' . $label);
        $shoppingList->setCurrent($current);
        $shoppingList->setLabel($label);
        $shoppingList->setWebsite($website);

        $manager->persist($shoppingList);
    }

    /**
     * @param EntityManager $manager
     * @param string $name
     * @return Website
     */
    protected function getWebsite(EntityManager $manager, $name)
    {
        if (!array_key_exists($name, $this->websites)) {
            $this->websites[$name] = $manager->getRepository('OroB2BWebsiteBundle:Website')
                ->findOneBy(['name' => $name]);
        }

        return $this->websites[$name];
    }
}
