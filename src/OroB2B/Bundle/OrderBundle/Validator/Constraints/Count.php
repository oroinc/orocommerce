<?php

namespace OroB2B\Bundle\OrderBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Count as BaseConstraint;

class Count extends BaseConstraint
{
    /**
     * {@inheritdoc}
     */
    public $min = 1;

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'Symfony\Component\Validator\Constraints\CountValidator';
    }
}
