<?php

namespace Oro\Bundle\PaymentBundle\Migrations;

use Oro\Bundle\PaymentBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentBundle\Migrations\Data\Demo\ORM\LoadPaymentTermDemoData;

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
