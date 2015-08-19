<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;

class LoadPaymentTermData extends AbstractFixture
{
    const TERM_LABEL_NET_10 = 'net 10';
    const TERM_LABEL_NET_20 = 'net 20';
    const TERM_LABEL_NET_30 = 'net 30';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $paymentTermsLabels = [
            self::TERM_LABEL_NET_10,
            self::TERM_LABEL_NET_20,
            self::TERM_LABEL_NET_30,
        ];

        foreach ($paymentTermsLabels as $paymentTermLabel) {
            $paymentTerm = new PaymentTerm();
            $paymentTerm->setLabel($paymentTermLabel);

            $manager->persist($paymentTerm);
            $this->addReference($paymentTermLabel, $paymentTerm);
        }

        $manager->flush();
    }
}
