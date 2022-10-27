<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Regex;

/**
 * Constraint to check is url safe.
 */
class UrlSafe extends Regex implements AliasAwareConstraintInterface
{
    const ALIAS = 'url_safe';

    /** @var string */
    public $message = 'This value should contain only latin letters, numbers and symbols "-._~".';

    /** @var string */
    public $pattern = '/^[a-zA-Z0-9\-\.\_\~]*$/';

    /**
     * {@inheritdoc}
     */
    public function __construct($options = null)
    {
        if ($options['allowSlashes'] ?? false) {
            $options['pattern'] = '/(?=[^\/])^[a-zA-Z0-9\-\.\_\~\/]*[^\/]$/';
            $options['message'] = 'This value should not start or end with "/" and should contain only latin letters, '
                . 'numbers and symbols "-._~/".';
        }

        unset($options['allowSlashes']);

        $options['pattern'] = $options['pattern'] ?? $this->pattern;

        parent::__construct($options);
    }

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
