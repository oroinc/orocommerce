<?php

namespace OroB2B\Bundle\OrderBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\PaymentBundle\Migrations\Data\Demo\ORM\LoadPaymentTermDemoData;

class LoadOrderDemoData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $addresses = [];

    /**
     * @var array
     */
    protected $paymentTerms = [];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\OrderBundle\Migrations\Data\Demo\ORM\LoadOrderAddressDemoData',
            'OroB2B\Bundle\PaymentBundle\Migrations\Data\Demo\ORM\LoadPaymentTermDemoData',
            'OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM\LoadAccountDemoData'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var User[] $users */
        $users = $manager->getRepository('OroUserBundle:User')->findAll();

        // create orders
        foreach ($users as $user) {
            $order = new Order();
            $order
                ->setOwner($user)
                ->setAccountUser($this->getAccountUser($manager))
                ->setOrganization($user->getOrganization())
                ->setIdentifier($user->getId())
                ->setBillingAddress($this->getOrderAddressByLabel($manager, LoadOrderAddressDemoData::ORDER_ADDRESS_1))
                ->setShippingAddress($this->getOrderAddressByLabel($manager, LoadOrderAddressDemoData::ORDER_ADDRESS_2))
                ->setPaymentTerm($this->getPaymentTermByLabel($manager, LoadPaymentTermDemoData::PAYMENT_TERM_NET_10))
                ->setShipUntil(new \DateTime())
                ->setCurrency('USD')
                ->setPoNumber('CV032342USDD')
                ->setSubtotal(15535.88);

            $manager->persist($order);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $label
     * @return PaymentTerm|null
     */
    protected function getPaymentTermByLabel(ObjectManager $manager, $label)
    {
        if (!array_key_exists($label, $this->paymentTerms)) {
            $this->paymentTerms[$label] = $manager->getRepository('OroB2BPaymentBundle:PaymentTerm')
                ->findOneBy(['label' => $label]);
        }

        return $this->paymentTerms[$label];
    }

    /**
     * @param ObjectManager $manager
     * @param string $label
     * @return OrderAddress|null
     */
    protected function getOrderAddressByLabel(ObjectManager $manager, $label)
    {
        if (!array_key_exists($label, $this->addresses)) {
            $this->addresses[$label] = $manager->getRepository('OroB2BOrderBundle:OrderAddress')
                ->findOneBy(['label' => $label]);
        }

        return $this->addresses[$label];
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
