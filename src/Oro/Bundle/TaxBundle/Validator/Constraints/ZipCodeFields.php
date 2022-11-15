<?php

namespace Oro\Bundle\TaxBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check tax jurisdiction zip codes.
 */
class ZipCodeFields extends Constraint
{
    public string $onlyOneTypeMessage = 'oro.tax.validator.constraints.single_or_range';
    public string $rangeShouldHaveBothFieldMessage = 'oro.tax.validator.constraints.range_start_and_end_required';
    public string $onlyNumericRangesSupported = 'oro.tax.validator.constraints.only_numeric_ranges_supported';
    public string $zipCodeCanNotBeEmpty = 'oro.tax.validator.constraints.zip_code_can_not_be_empty';

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
