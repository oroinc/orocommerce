<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserACLData;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class LoadShoppingListACLData extends AbstractFixture implements
    FixtureInterface,
    DependentFixtureInterface
{
    const SHOPPING_LIST_ACC_1_USER_LOCAL = 'shopping_list_account1_user_local';
    const SHOPPING_LIST_ACC_1_USER_BASIC = 'shopping_list_account1_user_basic';
    const SHOPPING_LIST_ACC_1_USER_DEEP = 'shopping_list_account1_user_deep';

    const SHOPPING_LIST_ACC_2_USER_LOCAL = 'shopping_list_account2_user_local';
    const SHOPPING_LIST_ACC_2_USER_BASIC = 'shopping_list_account2_user_basic';
    const SHOPPING_LIST_ACC_2_USER_DEEP = 'shopping_list_account2_user_deep';

    const SHOPPING_LIST_ACC_1_1_USER_LOCAL = 'shopping_list_account1.1_user_local';
    const SHOPPING_LIST_ACC_1_2_USER_LOCAL = 'shopping_list_account1.2_user_local';

    const LIST_LINE_ITEM_1 = 'shopping_list_line_item_1';

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
        self::SHOPPING_LIST_ACC_2_USER_LOCAL => [
            'accountUser' => LoadAccountUserACLData::USER_ACCOUNT_2_ROLE_LOCAL
        ],
        self::SHOPPING_LIST_ACC_2_USER_BASIC => [
            'accountUser' => LoadAccountUserACLData::USER_ACCOUNT_2_ROLE_BASIC
        ],
        self::SHOPPING_LIST_ACC_2_USER_DEEP => [
            'accountUser' => LoadAccountUserACLData::USER_ACCOUNT_2_ROLE_DEEP
        ],
        self::SHOPPING_LIST_ACC_1_1_USER_LOCAL => [
            'accountUser' => LoadAccountUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL
        ],
        self::SHOPPING_LIST_ACC_1_2_USER_LOCAL => [
            'accountUser' => LoadAccountUserACLData::USER_ACCOUNT_1_2_ROLE_LOCAL
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadShoppingListUserACLData::class,
            LoadProductUnitPrecisions::class,
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

        $this->createLineItem($manager);
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

    /**
     * @param ObjectManager $manager
     */
    protected function createLineItem(ObjectManager $manager)
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(self::SHOPPING_LIST_ACC_1_1_USER_LOCAL);
        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.bottle');
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $item = new LineItem();
        $item->setNotes('Test Notes')
            ->setAccountUser($shoppingList->getAccountUser())
            ->setOrganization($shoppingList->getOrganization())
            ->setShoppingList($shoppingList)
            ->setUnit($unit)
            ->setProduct($product)
            ->setQuantity('23.15');
        $manager->persist($item);
        $this->addReference(self::LIST_LINE_ITEM_1, $item);
    }
}
