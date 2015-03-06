<?php

namespace OroB2B\Bundle\ValidationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Regex;

class Letters extends Regex implements AliasAwareConstraintInterface
{
    const ALIAS = 'letters';

    public $message = 'This value should contain only latin letters.';
    public $pattern = '/^[a-zA-Z]*$/';

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredOptions()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'Symfony\Component\Validator\Constraints\RegexValidator';
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return self::ALIAS;
    }
}
