<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserACLData;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class LoadShoppingListACLData extends AbstractFixture implements
    FixtureInterface,
    DependentFixtureInterface
{
    const SHOPPING_LIST_ACC_1_USER_LOCAL = 'shopping_list_account1_user_local';
    const SHOPPING_LIST_ACC_1_USER_BASIC = 'shopping_list_account1_user_basic';
    const SHOPPING_LIST_ACC_1_USER_DEEP = 'shopping_list_account1_user_deep';

    const SHOPPING_LIST_ACC_1_1_USER_LOCAL = 'shopping_list_account1.1_user_local';

    /**
     * @var array
     */
    protected static $shoppingLists = [
        self::SHOPPING_LIST_ACC_1_USER_LOCAL => [
            'accountUser' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_LOCAL
        ],
        self::SHOPPING_LIST_ACC_1_USER_BASIC => [
            'accountUser' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_BASIC
        ],
        self::SHOPPING_LIST_ACC_1_USER_DEEP => [
            'accountUser' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_DEEP
        ],
        self::SHOPPING_LIST_ACC_1_1_USER_LOCAL => [
            'accountUser' => LoadAccountUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadShoppingListUserACLData::class,
        ];
    }

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$shoppingLists as $name => $order) {
            $this->createShoppingList($manager, $name, $order);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $name
     * @param array $orderData
     */
    protected function createShoppingList(ObjectManager $manager, $name, array $orderData)
    {
        /** @var AccountUser $accountUser */
        $accountUser = $this->getReference($orderData['accountUser']);

        $shoppingList = new ShoppingList();
        $shoppingList
            ->setLabel($name)
            ->setOrganization($accountUser->getOrganization())
            ->setAccount($accountUser->getAccount())
            ->setAccountUser($accountUser);
        $manager->persist($shoppingList);
        $this->addReference($name, $shoppingList);
    }
}
