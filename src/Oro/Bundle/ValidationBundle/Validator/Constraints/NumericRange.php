<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Oro\Component\Math\BigDecimal;
use Symfony\Component\Validator\Constraints\Range;

/**
 * Checks whether the value does not exceed the permissible range of Numeric.
 */
class NumericRange extends Range
{
    private const DEFAULT_MIN = 0;
    private const DEFAULT_PRECISION = 19;
    private const DEFAULT_SCALE = 4;

    /** @var float|string */
    public $min;

    /** @var float|string */
    public $max;

    /**
     * {@inheritdoc}
     */
    public function __construct($options = null)
    {
        $options['min'] = $options['min'] ?? self::DEFAULT_MIN;
        $options['max'] = $options['max'] ?? (string)BigDecimal::ofUnscaledValue(
            str_repeat('9', $this->getPrecision($options)),
            $this->getScale($options)
        );

        unset($options['precision'], $options['scale']);

        parent::__construct($options);
    }

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return NumericRangeValidator::class;
    }

    private function getPrecision(array $options): int
    {
        return $options['precision'] ?? self::DEFAULT_PRECISION;
    }

    private function getScale(array $options): int
    {
        return $options['scale'] ?? self::DEFAULT_SCALE;
    }
}
