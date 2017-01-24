<?php

namespace Oro\Bundle\DPDBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class RatesCsvFile extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.dpd.transport.rates_csv.invalid';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return RatesCsvFileValidator::ALIAS;
    }
}
