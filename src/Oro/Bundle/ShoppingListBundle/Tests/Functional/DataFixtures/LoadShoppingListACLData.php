<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserACLData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class LoadShoppingListACLData extends AbstractFixture implements DependentFixtureInterface
{
    public const SHOPPING_LIST_ACC_1_USER_LOCAL = 'shopping_list_customer1_user_local';
    public const SHOPPING_LIST_ACC_1_USER_BASIC = 'shopping_list_customer1_user_basic';
    public const SHOPPING_LIST_ACC_1_USER_DEEP = 'shopping_list_customer1_user_deep';

    public const SHOPPING_LIST_ACC_2_USER_LOCAL = 'shopping_list_customer2_user_local';
    public const SHOPPING_LIST_ACC_2_USER_BASIC = 'shopping_list_customer2_user_basic';
    public const SHOPPING_LIST_ACC_2_USER_DEEP = 'shopping_list_customer2_user_deep';

    public const SHOPPING_LIST_ACC_1_1_USER_LOCAL = 'shopping_list_customer1.1_user_local';
    public const SHOPPING_LIST_ACC_1_2_USER_LOCAL = 'shopping_list_customer1.2_user_local';

    public const LIST_LINE_ITEM_1 = 'shopping_list_line_item_1';

    private static array $shoppingLists = [
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

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadShoppingListUserACLData::class,
            LoadProductUnitPrecisions::class
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        foreach (self::$shoppingLists as $name => $order) {
            $this->createShoppingList($manager, $name, $order);
        }

        $this->createLineItem($manager);
        $manager->flush();
    }

    private function createShoppingList(ObjectManager $manager, string $name, array $orderData): void
    {
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getReference($orderData['customerUser']);

        $shoppingList = new ShoppingList();
        $shoppingList->setLabel($name);
        $shoppingList->setOrganization($customerUser->getOrganization());
        $shoppingList->setCustomer($customerUser->getCustomer());
        $shoppingList->setCustomerUser($customerUser);
        $shoppingList->setWebsite($manager->getRepository(Website::class)->getDefaultWebsite());
        $manager->persist($shoppingList);
        $this->addReference($name, $shoppingList);
    }

    private function createLineItem(ObjectManager $manager): void
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(self::SHOPPING_LIST_ACC_1_1_USER_LOCAL);
        $item = new LineItem();
        $item->setNotes('Test Notes');
        $item->setCustomerUser($shoppingList->getCustomerUser());
        $item->setOrganization($shoppingList->getOrganization());
        $item->setShoppingList($shoppingList);
        $item->setUnit($this->getReference('product_unit.bottle'));
        $item->setProduct($this->getReference(LoadProductData::PRODUCT_1));
        $item->setQuantity('23.15');
        $manager->persist($item);
        $this->addReference(self::LIST_LINE_ITEM_1, $item);
    }
}
