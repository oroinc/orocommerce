<?php

namespace OroB2B\Bundle\PaymentBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;

class LoadPaymentTermDemoData extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $paymentTermsLabels = [
            'net 10',
            'net 30',
            'net 60',
            'net 90',
        ];

        foreach ($paymentTermsLabels as $paymentTermLabel) {
            $paymentTerm = new PaymentTerm();
            $paymentTerm->setLabel($paymentTermLabel);

            $this->addReference($paymentTermLabel, $paymentTerm);
            $manager->persist($paymentTerm);
        }

        $manager->flush();
    }
}
