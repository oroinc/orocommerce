<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData as LoadBaseCustomerUserData;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

class LoadShoppingLists extends AbstractFixture implements DependentFixtureInterface
{
    const SHOPPING_LIST_1 = 'shopping_list_1';
    const SHOPPING_LIST_2 = 'shopping_list_2';
    const SHOPPING_LIST_3 = 'shopping_list_3';
    const SHOPPING_LIST_4 = 'shopping_list_4';
    const SHOPPING_LIST_5 = 'shopping_list_5';
    const SHOPPING_LIST_6 = 'shopping_list_6';
    const SHOPPING_LIST_7 = 'shopping_list_7';
    const SHOPPING_LIST_8 = 'shopping_list_8';
    const SHOPPING_LIST_9 = 'shopping_list_9';

    /**
     * @var array
     */
    protected $shoppingListWebsites = [
        self::SHOPPING_LIST_9 => LoadWebsiteData::WEBSITE3,
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadWebsiteData::class,
            LoadCustomerUserData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->getData() as $listLabel => $definition) {
            $isCurrent = $listLabel === self::SHOPPING_LIST_2;
            $this->createShoppingList(
                $manager,
                $this->getCustomerUser($manager, $definition['customerUser']),
                $listLabel,
                $isCurrent
            );
        }
        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param CustomerUser $customerUser
     * @param string $name
     * @param bool $isCurrent
     * @return ShoppingList
     */
    protected function createShoppingList(
        ObjectManager $manager,
        CustomerUser $customerUser,
        $name,
        $isCurrent = false
    ) {
        $shoppingList = new ShoppingList();
        $shoppingList->setOrganization($customerUser->getOrganization());
        $shoppingList->setCustomerUser($customerUser);
        $shoppingList->setCustomer($customerUser->getCustomer());
        $shoppingList->setLabel($name . '_label');
        $shoppingList->setNotes($name . '_notes');
        $shoppingList->setCurrent($isCurrent);
        if (array_key_exists($name, $this->shoppingListWebsites)) {
            $shoppingList->setWebsite($this->getReference($this->shoppingListWebsites[$name]));
        } else {
            $shoppingList->setWebsite($this->getDefaultWebsite($manager));
        }
        $manager->persist($shoppingList);
        $this->addReference($name, $shoppingList);

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
     * @return array
     */
    protected function getData()
    {
        return [
            self::SHOPPING_LIST_1 => [
                'customerUser' => LoadBaseCustomerUserData::AUTH_USER,
            ],
            self::SHOPPING_LIST_2 => [
                'customerUser' => LoadBaseCustomerUserData::AUTH_USER,
            ],
            self::SHOPPING_LIST_3 => [
                'customerUser' => LoadBaseCustomerUserData::AUTH_USER,
            ],
            self::SHOPPING_LIST_4 => [
                'customerUser' => LoadBaseCustomerUserData::AUTH_USER,
            ],
            self::SHOPPING_LIST_5 => [
                'customerUser' => LoadBaseCustomerUserData::AUTH_USER,
            ],
            self::SHOPPING_LIST_6 => [
                'customerUser' => LoadCustomerUserData::LEVEL_1_1_EMAIL,
            ],
            self::SHOPPING_LIST_7 => [
                'customerUser' => LoadCustomerUserData::LEVEL_1_EMAIL,
            ],
            self::SHOPPING_LIST_8 => [
                'customerUser' => LoadBaseCustomerUserData::AUTH_USER,
            ],
            self::SHOPPING_LIST_9 => [
                'customerUser' => LoadBaseCustomerUserData::AUTH_USER,
            ],
        ];
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
