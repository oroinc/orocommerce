<?php

namespace OroB2B\Bundle\PaymentBundle\Migrations;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\PaymentBundle\Migrations\Data\Demo\ORM\LoadPaymentTermDemoData;

trait PaymentTermDemoMigrationTrait
{
    /**
     * @return PaymentTerm[]
     */
    protected function getLoadedPaymentTerms()
    {
        /** @var \Doctrine\Common\DataFixtures\AbstractFixture $this */
        $paymentTerms = [];
        foreach (LoadPaymentTermDemoData::$paymentTermsLabels as $paymentTermLabel) {
            $paymentTerms[] = $this->getReference($paymentTermLabel);
        }

        return $paymentTerms;
    }
}
