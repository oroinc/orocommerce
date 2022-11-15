<?php

namespace Oro\Bundle\TaxBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check whether a tax code is unique.
 */
class UniqueTaxCode extends Constraint
{
    public $message = 'oro.tax.validator.constraints.not_unique_tax_code';

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
