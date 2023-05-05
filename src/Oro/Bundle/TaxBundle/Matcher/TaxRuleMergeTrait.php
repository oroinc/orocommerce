<?php

namespace Oro\Bundle\TaxBundle\Matcher;

use Oro\Bundle\TaxBundle\Entity\TaxRule;

/**
 * Provides a method to merge tax rules.
 */
trait TaxRuleMergeTrait
{
    private function mergeTaxRules(...$allTaxRules): array
    {
        $result = [];
        /** @var TaxRule[] $taxRules */
        foreach ($allTaxRules as $taxRules) {
            foreach ($taxRules as $taxRule) {
                $result[$taxRule->getId()] = $taxRule;
            }
        }

        return array_values($result);
    }
}
