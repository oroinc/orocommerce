<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\GreaterThan;

class GreaterThanZero extends GreaterThan implements AliasAwareConstraintInterface
{
    const ALIAS = 'greater_than_zero';

    public $value = 0;

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
