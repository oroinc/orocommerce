<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData as TestAccountUserData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTerm;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadOrders extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    const ORDER_1 = 'simple_order';
    const MY_ORDER = 'my_order';
    const ACCOUNT_USER = 'grzegorz.brzeczyszczykiewicz@example.com';
    const SUBTOTAL = '789';
    const TOTAL = '1234';

    /**
     * @var array
     */
    protected $orders = [
        self::ORDER_1 => [
            'user' => LoadOrderUsers::ORDER_USER_1,
            'accountUser' => self::ACCOUNT_USER,
            'poNumber' => '1234567890',
            'customerNotes' => 'Test account user notes',
            'currency' => 'USD',
            'subtotal' => self::SUBTOTAL,
            'total' => self::TOTAL,
            'paymentTerm' => LoadPaymentTermData::PAYMENT_TERM_NET_10,
        ],
        self::MY_ORDER => [
            'user' => LoadOrderUsers::ORDER_USER_1,
            'accountUser' => TestAccountUserData::AUTH_USER,
            'poNumber' => 'PO_NUM',
            'customerNotes' => 'Test account user notes',
            'currency' => 'EUR',
            'subtotal' => '1500',
            'total' => '1700',
            'paymentTerm' => LoadPaymentTermData::PAYMENT_TERM_NET_10,
        ],
    ];

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Website
     */
    protected $defaultWebsite;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadAccountUserData::class,
            LoadOrderUsers::class,
            LoadPaymentTermData::class
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
        if (!$user->getOrganization()) {
            $user->setOrganization($manager->getRepository('OroOrganizationBundle:Organization')->findOneBy([]));
        }
        /** @var AccountUser $accountUser */
        $accountUser = $manager->getRepository('OroCustomerBundle:AccountUser')
            ->findOneBy(['username' => $orderData['accountUser']]);

        /** @var PaymentTerm $paymentTerm */
        $paymentTerm = $this->getReference($orderData['paymentTerm']);

        $website = $this->getDefaultWebsite();

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
            ->setTotal($orderData['total'])
            ->setAccount($accountUser->getAccount())
            ->setWebsite($website)
            ->setAccountUser($accountUser);

        $manager->persist($order);
        $this->addReference($name, $order);

        return $order;
    }

    /**
     * @return Website
     */
    protected function getDefaultWebsite()
    {
        if (!$this->defaultWebsite) {
            $this->defaultWebsite = $this->container->get('doctrine')
                ->getRepository(Website::class)
                ->findOneBy(['default' => true]);
        }

        return $this->defaultWebsite;
    }
}
