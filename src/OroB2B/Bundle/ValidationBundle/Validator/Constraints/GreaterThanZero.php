<?php

namespace OroB2B\Bundle\ValidationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\GreaterThan;

class GreaterThanZero extends GreaterThan implements AliasAwareConstraintInterface
{
    const ALIAS = 'greater_than_zero';

    public $value = 0;

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'Symfony\Component\Validator\Constraints\GreaterThanValidator';
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return self::ALIAS;
    }
}
