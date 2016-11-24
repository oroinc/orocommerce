<?php

namespace Oro\Bundle\PaymentTermBundle\Migrations\Data\Demo\ORM;

use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Migrations\Data\Demo\ORM\LoadPaymentTermDemoData;

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
