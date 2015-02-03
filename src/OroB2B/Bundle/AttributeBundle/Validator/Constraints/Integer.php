<?php

namespace OroB2B\Bundle\AttributeBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class Integer extends Constraint implements AttributeConstraintInterface
{
    const ALIAS = 'integer';

    public $message = 'This value should be integer number.';

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return self::ALIAS;
    }
}
