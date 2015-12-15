<?php

namespace OroB2B\Bundle\TaxBundle\Validator;

use Symfony\Component\Validator\Constraint;

class ZipCodeFields extends Constraint
{
    public $message = 'Zip code should have only single code or range';

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
