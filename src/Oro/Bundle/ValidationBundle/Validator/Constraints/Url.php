<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Url as BaseUrl;

/**
 * Validates that a value is a valid URL.
 *
 * This constraint extends Symfony's Url constraint to add alias support through {@see AliasAwareConstraintInterface}.
 * It delegates validation to Symfony's {@see UrlValidator} while providing a simple `url` alias
 * that can be used in client-side validation and form field data attributes.
 * This allows the constraint to be easily referenced in JavaScript validation rules
 * without using the fully-qualified class name.
 */
class Url extends BaseUrl implements AliasAwareConstraintInterface
{
    const ALIAS = 'url';

    #[\Override]
    public function validatedBy(): string
    {
        return 'Symfony\Component\Validator\Constraints\UrlValidator';
    }

    #[\Override]
    public function getAlias()
    {
        return self::ALIAS;
    }
}
