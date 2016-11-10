<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserACLData;
use Oro\Bundle\OrderBundle\Entity\Order;

class LoadOrdersACLData extends AbstractFixture implements
    FixtureInterface,
//    ContainerAwareInterface,
    DependentFixtureInterface
{
    const ORDER_ACC_1_USER_LOCAL = 'order_account1_user_local';
    const ORDER_ACC_1_USER_BASIC = 'order_account1_user_basic';
    const ORDER_ACC_1_USER_DEEP = 'order_account1_user_deep';

    const ORDER_ACC_1_1_USER_LOCAL = 'order_account1.1_user_local';

    /**
     * @var array
     */
    protected static $orders = [
        self::ORDER_ACC_1_USER_LOCAL => [
            'accountUser' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_LOCAL
        ],
        self::ORDER_ACC_1_USER_BASIC => [
            'accountUser' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_BASIC
        ],
        self::ORDER_ACC_1_USER_DEEP => [
            'accountUser' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_DEEP
        ],
        self::ORDER_ACC_1_1_USER_LOCAL => [
            'accountUser' => LoadAccountUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadOrderUserACLData::class,
        ];
    }

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$orders as $name => $order) {
            $this->createOrder($manager, $name, $order);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $name
     * @param array $orderData
     */
    protected function createOrder(ObjectManager $manager, $name, array $orderData)
    {
        /** @var AccountUser $accountUser */
        $accountUser = $this->getReference($orderData['accountUser']);

        $order = new Order();
        $order
            ->setIdentifier($name)
            ->setOrganization($accountUser->getOrganization())
            ->setAccount($accountUser->getAccount())
            ->setAccountUser($accountUser);
        $manager->persist($order);
        $this->addReference($name, $order);
    }
}
