<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;

class LoadPaymentTermData extends AbstractFixture
{
    const PAYMENT_TERM_NET_10 = 'payment_term.net_10';
    const PAYMENT_TERM_NET_20 = 'payment_term.net_20';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $paymentTerm = new PaymentTerm();
        $paymentTerm->setLabel('net 10');

        $paymentTerm20 = new PaymentTerm();
        $paymentTerm20->setLabel('net 20');

        $manager->persist($paymentTerm);
        $manager->persist($paymentTerm20);
        $manager->flush();

        $this->addReference(self::PAYMENT_TERM_NET_10, $paymentTerm);
        $this->addReference(self::PAYMENT_TERM_NET_20, $paymentTerm20);
    }
}
