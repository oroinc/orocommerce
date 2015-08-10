<?php

namespace OroB2B\Bundle\OrderBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;

class LoadOrderDemoData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $paymentTerms = [];

    /**
     * @var array
     */
    protected $orders = [
        [
            'billingAddress' => 'Billing Address 01',
            'shippingAddress' => 'Shipping Address 01',
            'subtotal' => 15535.88,
            'currency' => 'USD',
            'poNumber' => 'CV032342USDD',
            'paymentTerm' => 'net 10',
            'customerNotes' => 'Please, call before delivery'
        ],
        [
            'billingAddress' => 'Billing Address 02',
            'shippingAddress' => 'Shipping Address 02',
            'subtotal' => 20100.00,
            'currency' => 'USD',
            'poNumber' => 'AB10100USD',
            'paymentTerm' => 'net 15',
            'customerNotes' => 'Please, contact sales'
        ],
        [
            'billingAddress' => 'Billing Address 03',
            'shippingAddress' => 'Shipping Address 03',
            'subtotal' => 99.99,
            'currency' => 'EUR',
            'poNumber' => 'FA1000EUR',
            'paymentTerm' => 'net 10'
        ],
        [
            'billingAddress' => 'Billing Address 04',
            'shippingAddress' => 'Shipping Address 04',
            'subtotal' => 5600.50,
            'currency' => 'EUR',
            'poNumber' => 'RT104568EUR',
            'paymentTerm' => 'net 15'
        ],
        [
            'billingAddress' => 'Billing Address 05',
            'shippingAddress' => 'Shipping Address 05',
            'subtotal' => 7800.99,
            'currency' => 'USD',
            'poNumber' => 'RT104568000',
            'paymentTerm' => 'net 15'
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\PaymentBundle\Migrations\Data\Demo\ORM\LoadPaymentTermDemoData',
            'OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM\LoadAccountDemoData',
            'OroB2B\Bundle\OrderBundle\Migrations\Data\Demo\ORM\LoadOrderAddressDemoData'
        ];
    }

    /**
     * @param EntityManager $manager
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var EntityRepository $userRepository */
        $userRepository = $manager->getRepository('OroUserBundle:User');

        /** @var User $user */
        $user = $userRepository->findOneBy([]);

        $accountUser = $this->getAccountUser($manager);

        foreach ($this->orders as $orderData) {
            $order = new Order();

            /** @var OrderAddress $billingAddress */
            $billingAddress = $this->getReference($orderData['billingAddress']);

            /** @var OrderAddress $shippingAddress */
            $shippingAddress = $this->getReference($orderData['shippingAddress']);

            /** @var PaymentTerm $paymentTerm */
            $paymentTerm = $this->getReference($orderData['paymentTerm']);

            $order
                ->setOwner($user)
                ->setAccount($accountUser->getAccount())
                ->setAccountUser($accountUser)
                ->setOrganization($user->getOrganization())
                ->setBillingAddress($billingAddress)
                ->setShippingAddress($shippingAddress)
                ->setPaymentTerm($paymentTerm)
                ->setShipUntil(new \DateTime())
                ->setCurrency($orderData['currency'])
                ->setPoNumber($orderData['poNumber'])
                ->setSubtotal($orderData['subtotal']);

            if (isset($orderData['customerNotes'])) {
                $order->setCustomerNotes($orderData['customerNotes']);
            }

            $manager->persist($order);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @return AccountUser|null
     */
    protected function getAccountUser(ObjectManager $manager)
    {
        return $manager->getRepository('OroB2BAccountBundle:AccountUser')->findOneBy([]);
    }
}
