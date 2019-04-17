<?php

namespace Oro\Bundle\TaxBundle\Validator\Constraints;

use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates Tax Rate field to not contain more than 4 decimal points in percent representation,
 * which corresponds to 6 decimal points in decimal representation
 */
class TaxRateValidator extends ConstraintValidator
{
    const ALIAS = 'oro_tax_tax_rate';

    /**
     * {@inheritdoc}
     * @param TaxRate $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!is_float($value)) {
            return;
        }

        if ($this->isMoreDecimalPlacesThan($value, TaxationSettingsProvider::CALCULATION_SCALE)) {
            $this->context->addViolation($constraint->taxRateToManyDecimalPlaces);
        }
    }

    /**
     * @param float $value
     * @param int $decimalPlaces
     * @return bool
     */
    private function isMoreDecimalPlacesThan($value, $decimalPlaces): bool
    {
        [$mantissa, $exponent] = explode('e', sprintf('%.14e', $value));

        $mantissa = rtrim($mantissa, '0');
        $fractionLength = strlen($mantissa) - $exponent - 2; // we subtract 2 because of the dot and a digit before it

        return $fractionLength > $decimalPlaces;
    }
}
