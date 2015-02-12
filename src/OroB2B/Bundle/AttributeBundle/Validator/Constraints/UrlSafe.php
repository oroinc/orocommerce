<?php

namespace OroB2B\Bundle\AttributeBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Regex;

class UrlSafe extends Regex implements AttributeConstraintInterface
{
    const ALIAS = 'url_safe';

    public $message = 'This value should contain only latin letters, numbers and symbols "-._~".';
    public $pattern = '/^[a-zA-Z0-9\-\.\_\~]*$/';

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
