<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;

class LoadPaymentTermData extends AbstractFixture
{
    const PAYMENT_TERM_NET_10 = 'payment_term.net_10';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $paymentTerm = new PaymentTerm();
        $paymentTerm->setLabel('net 10');

        $manager->persist($paymentTerm);
        $manager->flush();

        $this->addReference(self::PAYMENT_TERM_NET_10, $paymentTerm);
    }
}
