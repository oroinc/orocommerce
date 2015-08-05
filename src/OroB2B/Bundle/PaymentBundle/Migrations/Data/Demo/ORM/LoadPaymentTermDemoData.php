<?php

namespace OroB2B\Bundle\PaymentBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;

class LoadPaymentTermDemoData extends AbstractFixture
{
    const PAYMENT_TERM_NET_10 = 'net 10';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $paymentTermsLabels = [
            self::PAYMENT_TERM_NET_10,
            'net 15',
            'net 30',
            'net 60',
        ];

        foreach ($paymentTermsLabels as $paymentTermLabel) {
            $paymentTerm = new PaymentTerm();
            $paymentTerm->setLabel($paymentTermLabel);

            $manager->persist($paymentTerm);
        }

        $manager->flush();
    }
}
