<?php

namespace OroB2B\Bundle\ValidationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class Integer extends Constraint implements AliasAwareConstraintInterface
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
