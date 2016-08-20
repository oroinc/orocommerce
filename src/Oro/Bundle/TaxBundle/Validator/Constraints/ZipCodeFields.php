<?php

namespace Oro\Bundle\TaxBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ZipCodeFields extends Constraint
{
    /**
     * @var string
     */
    public $onlyOneTypeMessage = 'oro.tax.validator.constraints.single_or_range';

    /**
     * @var string
     */
    public $rangeShouldHaveBothFieldMessage = 'oro.tax.validator.constraints.range_start_and_end_required';

    /**
     * @var string
     */
    public $onlyNumericRangesSupported = 'oro.tax.validator.constraints.only_numeric_ranges_supported';

    /**
     * @var string
     */
    public $zipCodeCanNotBeEmpty = 'oro.tax.validator.constraints.zip_code_can_not_be_empty';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return ZipCodeFieldsValidator::ALIAS;
    }
}
