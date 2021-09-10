<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserACLData;
use Oro\Bundle\OrderBundle\Entity\Order;

class LoadOrdersACLData extends AbstractFixture implements
    FixtureInterface,
    DependentFixtureInterface
{
    const ORDER_ACC_1_USER_LOCAL = 'order_customer1_user_local';
    const ORDER_ACC_1_USER_BASIC = 'order_customer1_user_basic';
    const ORDER_ACC_1_USER_DEEP = 'order_customer1_user_deep';

    const ORDER_ACC_1_1_USER_LOCAL = 'order_customer1.1_user_local';

    const ORDER_ACC_2_USER_LOCAL = 'order_customer2_user_local';

    /**
     * @var array
     */
    protected static $orders = [
        self::ORDER_ACC_1_USER_LOCAL => [
            'customerUser' => LoadCustomerUserACLData::USER_ACCOUNT_1_ROLE_LOCAL
        ],
        self::ORDER_ACC_1_USER_BASIC => [
            'customerUser' => LoadCustomerUserACLData::USER_ACCOUNT_1_ROLE_BASIC
        ],
        self::ORDER_ACC_1_USER_DEEP => [
            'customerUser' => LoadCustomerUserACLData::USER_ACCOUNT_1_ROLE_DEEP
        ],
        self::ORDER_ACC_1_1_USER_LOCAL => [
            'customerUser' => LoadCustomerUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL
        ],
        self::ORDER_ACC_2_USER_LOCAL => [
            'customerUser' => LoadCustomerUserACLData::USER_ACCOUNT_2_ROLE_LOCAL
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
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getReference($orderData['customerUser']);

        $order = new Order();
        $order
            ->setIdentifier($name)
            ->setOrganization($customerUser->getOrganization())
            ->setCustomer($customerUser->getCustomer())
            ->setCustomerUser($customerUser);
        $manager->persist($order);
        $this->addReference($name, $order);
    }
}
