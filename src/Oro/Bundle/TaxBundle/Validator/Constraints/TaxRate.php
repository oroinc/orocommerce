<?php

namespace Oro\Bundle\TaxBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint for TaxRateValidator
 */
class TaxRate extends Constraint
{
    /**
     * @var string
     */
    public $taxRateToManyDecimalPlaces = 'oro.tax.validator.constraints.tax_rate_to_many_decimal_places';

    /**
     * {@inheritdoc}
     */
    public function getTargets(): string|array
    {
        return self::PROPERTY_CONSTRAINT;
    }

    /**
     * {@inheritdoc}
     */
    public function validatedBy(): string
    {
        return TaxRateValidator::ALIAS;
    }
}
