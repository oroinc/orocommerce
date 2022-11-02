<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserACLData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class LoadShoppingListACLData extends AbstractFixture implements DependentFixtureInterface
{
    const SHOPPING_LIST_ACC_1_USER_LOCAL = 'shopping_list_customer1_user_local';
    const SHOPPING_LIST_ACC_1_USER_BASIC = 'shopping_list_customer1_user_basic';
    const SHOPPING_LIST_ACC_1_USER_DEEP = 'shopping_list_customer1_user_deep';

    const SHOPPING_LIST_ACC_2_USER_LOCAL = 'shopping_list_customer2_user_local';
    const SHOPPING_LIST_ACC_2_USER_BASIC = 'shopping_list_customer2_user_basic';
    const SHOPPING_LIST_ACC_2_USER_DEEP = 'shopping_list_customer2_user_deep';

    const SHOPPING_LIST_ACC_1_1_USER_LOCAL = 'shopping_list_customer1.1_user_local';
    const SHOPPING_LIST_ACC_1_2_USER_LOCAL = 'shopping_list_customer1.2_user_local';

    const LIST_LINE_ITEM_1 = 'shopping_list_line_item_1';

    /**
     * @var array
     */
    protected static $shoppingLists = [
        self::SHOPPING_LIST_ACC_1_USER_LOCAL => [
            'customerUser' => LoadCustomerUserACLData::USER_ACCOUNT_1_ROLE_LOCAL
        ],
        self::SHOPPING_LIST_ACC_1_USER_BASIC => [
            'customerUser' => LoadCustomerUserACLData::USER_ACCOUNT_1_ROLE_BASIC
        ],
        self::SHOPPING_LIST_ACC_1_USER_DEEP => [
            'customerUser' => LoadCustomerUserACLData::USER_ACCOUNT_1_ROLE_DEEP
        ],
        self::SHOPPING_LIST_ACC_2_USER_LOCAL => [
            'customerUser' => LoadCustomerUserACLData::USER_ACCOUNT_2_ROLE_LOCAL
        ],
        self::SHOPPING_LIST_ACC_2_USER_BASIC => [
            'customerUser' => LoadCustomerUserACLData::USER_ACCOUNT_2_ROLE_BASIC
        ],
        self::SHOPPING_LIST_ACC_2_USER_DEEP => [
            'customerUser' => LoadCustomerUserACLData::USER_ACCOUNT_2_ROLE_DEEP
        ],
        self::SHOPPING_LIST_ACC_1_1_USER_LOCAL => [
            'customerUser' => LoadCustomerUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL
        ],
        self::SHOPPING_LIST_ACC_1_2_USER_LOCAL => [
            'customerUser' => LoadCustomerUserACLData::USER_ACCOUNT_1_2_ROLE_LOCAL
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
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getReference($orderData['customerUser']);

        /** @var Website $website */
        $website = $manager->getRepository(Website::class)->getDefaultWebsite();

        $shoppingList = new ShoppingList();
        $shoppingList
            ->setLabel($name)
            ->setOrganization($customerUser->getOrganization())
            ->setCustomer($customerUser->getCustomer())
            ->setCustomerUser($customerUser)
            ->setWebsite($website);
        $manager->persist($shoppingList);
        $this->addReference($name, $shoppingList);
    }

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
            ->setCustomerUser($shoppingList->getCustomerUser())
            ->setOrganization($shoppingList->getOrganization())
            ->setShoppingList($shoppingList)
            ->setUnit($unit)
            ->setProduct($product)
            ->setQuantity('23.15');
        $manager->persist($item);
        $this->addReference(self::LIST_LINE_ITEM_1, $item);
    }
}
