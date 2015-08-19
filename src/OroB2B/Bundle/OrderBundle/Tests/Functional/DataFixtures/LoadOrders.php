<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;

class LoadOrders extends AbstractFixture implements DependentFixtureInterface
{
    const ORDER_1 = 'simple_order';
    const MY_ORDER = 'my_order';

    /**
     * @var array
     */
    protected $orders = [
        self::ORDER_1 => [
            'user' => LoadOrderUsers::ORDER_USER_1,
            'accountUser' => 'grzegorz.brzeczyszczykiewicz@example.com',
            'poNumber' => '1234567890',
            'customerNotes' => 'Test account user notes',
            'currency' => 'USD',
            'subtotal' => '15000',
            'paymentTerm' => LoadPaymentTermData::PAYMENT_TERM_NET_10
        ],
        self::MY_ORDER => [
            'user' => LoadOrderUsers::ORDER_USER_1,
            'accountUser' => LoadAccountUserData::AUTH_USER,
            'poNumber' => 'PO_NUM',
            'customerNotes' => 'Test account user notes',
            'currency' => 'EUR',
            'subtotal' => '1500',
            'paymentTerm' => LoadPaymentTermData::PAYMENT_TERM_NET_10
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData',
            'OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderUsers',
            'OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadPaymentTermData',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->orders as $name => $order) {
            $this->createOrder($manager, $name, $order);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $name
     * @param array $orderData
     * @return Order
     */
    protected function createOrder(ObjectManager $manager, $name, array $orderData)
    {
        /** @var User $user */
        $user = $this->getReference($orderData['user']);
        /** @var AccountUser $accountUser */
        $accountUser = $manager->getRepository('OroB2BAccountBundle:AccountUser')
            ->findOneBy(['username' => $orderData['accountUser']]);

        /** @var PaymentTerm $paymentTerm */
        $paymentTerm = $this->getReference($orderData['paymentTerm']);

        $order = new Order();
        $order
            ->setIdentifier($name)
            ->setOwner($user)
            ->setOrganization($user->getOrganization())
            ->setPaymentTerm($paymentTerm)
            ->setShipUntil(new \DateTime())
            ->setCurrency($orderData['currency'])
            ->setPoNumber($orderData['poNumber'])
            ->setSubtotal($orderData['subtotal'])
            ->setAccount($accountUser->getAccount())
            ->setAccountUser($accountUser);

        $manager->persist($order);
        $this->addReference($name, $order);

        return $order;
    }
}
