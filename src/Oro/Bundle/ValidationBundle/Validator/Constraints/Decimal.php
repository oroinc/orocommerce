<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that a value is a valid decimal number.
 *
 * This constraint extends Symfony's base {@see Constraint} to validate decimal numbers with support for locale-specific
 * formatting. It accepts both plain numeric values and locale-formatted strings
 * (e.g., "1,234.56" in en_US or "1.234,56" in de_DE). The validation is performed by {@see DecimalValidator},
 * which uses PHP's NumberFormatter to parse and validate the input according to the current locale settings.
 */
class Decimal extends Constraint implements AliasAwareConstraintInterface
{
    public const ALIAS = 'decimal';

    public $message = 'This value should be decimal number.';

    #[\Override]
    public function getAlias()
    {
        return self::ALIAS;
    }
}
