<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\GreaterThan;

class GreaterThanZero extends GreaterThan implements AliasAwareConstraintInterface
{
    const ALIAS = 'greater_than_zero';

    public $value = 0;

    /**
     * {@inheritdoc}
     */
    public function __construct($options = null)
    {
        $options['value'] = $this->value;
        parent::__construct($options);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function validatedBy(): string
    {
        return 'Symfony\Component\Validator\Constraints\GreaterThanValidator';
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return self::ALIAS;
    }
}
