<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserACLData;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

class LoadCheckoutACLData extends AbstractFixture implements
    FixtureInterface,
    DependentFixtureInterface
{
    const CHECKOUT_ACC_1_USER_LOCAL = 'checkout_account1_user_local';
    const CHECKOUT_ACC_1_USER_BASIC = 'checkout_account1_user_basic';
    const CHECKOUT_ACC_1_USER_DEEP = 'checkout_account1_user_deep';

    const CHECKOUT_ACC_1_1_USER_LOCAL = 'checkout_account1.1_user_local';

    const CHECKOUT_ACC_2_USER_LOCAL = 'checkout_account2_user_local';

    /**
     * @var array
     */
    protected static $checkouts = [
        self::CHECKOUT_ACC_1_USER_LOCAL => [
            'accountUser' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_LOCAL
        ],
        self::CHECKOUT_ACC_1_USER_BASIC => [
            'accountUser' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_BASIC
        ],
        self::CHECKOUT_ACC_1_USER_DEEP => [
            'accountUser' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_DEEP
        ],
        self::CHECKOUT_ACC_1_1_USER_LOCAL => [
            'accountUser' => LoadAccountUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL
        ],
        self::CHECKOUT_ACC_2_USER_LOCAL => [
            'accountUser' => LoadAccountUserACLData::USER_ACCOUNT_2_ROLE_LOCAL
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadCheckoutUserACLData::class,
            LoadShoppingLists::class,
            LoadWebsiteData::class,
        ];
    }

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$checkouts as $name => $checkout) {
            $this->createOrder($manager, $name, $checkout);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $name
     * @param array $checkoutData
     */
    protected function createOrder(ObjectManager $manager, $name, array $checkoutData)
    {
        /** @var AccountUser $accountUser */
        $accountUser = $this->getReference($checkoutData['accountUser']);
        $shoppingList = new ShoppingList();
        $shoppingList->setOrganization($accountUser->getOrganization())
            ->setAccount($accountUser->getAccount())
            ->setAccountUser($accountUser)
            ->setLabel('test');
        $manager->persist($shoppingList);

        $source = new CheckoutSource();
        /** @noinspection PhpUndefinedMethodInspection - field added through entity extend */
        $source->setShoppingList($shoppingList);
        $manager->persist($source);
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        $checkout = new Checkout();
        $checkout
            ->setSource($source)
            ->setWebsite($website)
            ->setOrganization($accountUser->getOrganization())
            ->setAccount($accountUser->getAccount())
            ->setAccountUser($accountUser);
        $manager->persist($checkout);
        $this->addReference($name, $checkout);
    }
}
