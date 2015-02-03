<?php

namespace OroB2B\Bundle\AttributeBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class Decimal extends Constraint implements AttributeConstraintInterface
{
    const ALIAS = 'decimal';

    public $message = 'This value should be decimal number.';

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return self::ALIAS;
    }
}
