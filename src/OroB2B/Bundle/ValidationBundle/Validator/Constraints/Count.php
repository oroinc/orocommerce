<?php

namespace OroB2B\Bundle\ValidationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Count as BaseConstraint;

/**
 * @deprecated Regular Symfony Count constraint must be used instead,
 *             this constraint will be removed in scope of BB-2870
 */
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
