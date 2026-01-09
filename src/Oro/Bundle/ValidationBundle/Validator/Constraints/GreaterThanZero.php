<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\GreaterThan;

/**
 * Validates that a value is greater than zero.
 */
class GreaterThanZero extends GreaterThan implements AliasAwareConstraintInterface
{
    public const ALIAS = 'greater_than_zero';

    public mixed $value = 0;

    public function __construct($options = null)
    {
        $options['value'] = $this->value;
        parent::__construct($options);
    }

    #[\Override]
    public function getDefaultOption(): ?string
    {
        return null;
    }

    #[\Override]
    public function validatedBy(): string
    {
        return 'Symfony\Component\Validator\Constraints\GreaterThanValidator';
    }

    #[\Override]
    public function getAlias()
    {
        return self::ALIAS;
    }
}
