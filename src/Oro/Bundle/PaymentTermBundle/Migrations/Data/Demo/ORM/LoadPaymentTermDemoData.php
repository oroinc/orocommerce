<?php

namespace Oro\Bundle\PaymentTermBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;

/**
 * Loads demo payment term data into the database.
 *
 * This fixture creates standard payment term entities (net 10, net 30, net 60, net 90)
 * and registers them as references for use by other demo data fixtures.
 * These payment terms are used to populate demo data for customers and orders.
 */
class LoadPaymentTermDemoData extends AbstractFixture
{
    /**
     * @var array
     */
    public static $paymentTermsLabels = [
        'net 10',
        'net 30',
        'net 60',
        'net 90',
    ];

    #[\Override]
    public function load(ObjectManager $manager)
    {
        foreach (self::$paymentTermsLabels as $paymentTermLabel) {
            $paymentTerm = new PaymentTerm();
            $paymentTerm->setLabel($paymentTermLabel);

            $this->addReference($paymentTermLabel, $paymentTerm);
            $manager->persist($paymentTerm);
        }

        $manager->flush();
    }
}
