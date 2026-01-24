<?php

namespace Oro\Bundle\PaymentTermBundle\Migrations\Data\Demo\ORM;

use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;

/**
 * Provides utility methods for accessing loaded payment term demo data.
 *
 * This trait is used by demo data fixtures to retrieve payment term references that were previously loaded
 * by LoadPaymentTermDemoData, allowing other fixtures to associate payment terms with entities during demo data setup.
 */
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
