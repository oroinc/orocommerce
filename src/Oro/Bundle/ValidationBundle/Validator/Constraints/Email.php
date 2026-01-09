<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Email as BaseEmail;

/**
 * Validates that a value is a valid email address.
 *
 * This constraint extends Symfony's Email constraint to add alias support through
 * {@see AliasAwareConstraintInterface}. It delegates validation to Symfony's {@see EmailValidator}
 * while providing a simple `email` alias that can be used in client-side validation and form field data attributes.
 * This allows the constraint to be easily referenced in JavaScript validation rules
 * without using the fully-qualified class name.
 */
class Email extends BaseEmail implements AliasAwareConstraintInterface
{
    public const ALIAS = 'email';

    #[\Override]
    public function validatedBy(): string
    {
        return 'Symfony\Component\Validator\Constraints\EmailValidator';
    }

    #[\Override]
    public function getAlias()
    {
        return self::ALIAS;
    }
}
