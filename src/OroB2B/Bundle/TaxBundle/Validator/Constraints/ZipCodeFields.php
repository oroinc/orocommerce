<?php

namespace OroB2B\Bundle\TaxBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ZipCodeFields extends Constraint
{
    public $onlyOneTypeMessage = 'Zip code has to have only code or range';
    public $rangeShouldHaveBothFieldMessage = 'Zip code range has to have start and end';

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
