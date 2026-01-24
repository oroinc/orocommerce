<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that a value is a valid integer number.
 *
 * This constraint extends Symfony's base Constraint to validate integer numbers with support
 * for locale-specific formatting. It accepts both plain numeric values and locale-formatted strings
 * (e.g., "1,234" in en_US or "1.234" in de_DE). The validation is performed by {@see IntegerValidator},
 * which uses PHP's NumberFormatter to parse the input according to the current locale settings
 * and ensures the value contains no decimal separator.
 */
class Integer extends Constraint implements AliasAwareConstraintInterface
{
    const ALIAS = 'integer';

    public $message = 'This value should be integer number.';

    #[\Override]
    public function getAlias()
    {
        return self::ALIAS;
    }
}
