<?php

namespace OroB2B\Bundle\InvoiceBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\InvoiceBundle\Entity\Invoice;

class LoadInvoices extends AbstractFixture implements DependentFixtureInterface
{
    const ORDER_1 = 'simple_order';
    const MY_ORDER = 'my_order';
    const ACCOUNT_USER = 'grzegorz.brzeczyszczykiewicz@example.com';
    const SUBTOTAL = '789';

    /**
     * @var array
     */
    protected $orders = [
        self::ORDER_1 => [
            'poNumber' => '1234567890',
            'currency' => 'USD',
            'subtotal' => self::SUBTOTAL,
        ],
        self::MY_ORDER => [
            'accountUser' => LoadAccountUserData::AUTH_USER,
            'poNumber' => 'PO_NUM',
            'currency' => 'EUR',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
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
        if (!$user->getOrganization()) {
            $user->setOrganization($manager->getRepository('OroOrganizationBundle:Organization')->findOneBy([]));
        }
        /** @var AccountUser $accountUser */
        $accountUser = $manager->getRepository('OroB2BAccountBundle:AccountUser')
            ->findOneBy(['username' => $orderData['accountUser']]);

        $order = new Invoice();
        $order
            ->setInvoiceNumber($name)
            ->setOwner($user)
            ->setOrganization($user->getOrganization())
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
