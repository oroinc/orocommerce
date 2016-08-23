<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Regex;

class AlphanumericDashUnderscore extends Regex implements AliasAwareConstraintInterface
{
    const ALIAS = 'alphanumeric_dash_underscore';

    public $message = 'This value should contain only latin letters, numbers and symbols "-" or "_".';
    public $pattern = '/^[-_a-zA-Z0-9]*$/';

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
        return [];
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
