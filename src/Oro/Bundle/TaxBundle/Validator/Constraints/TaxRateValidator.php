<?php

namespace Oro\Bundle\TaxBundle\Validator\Constraints;

use Oro\Bundle\TaxBundle\Form\Type\TaxType;
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

        if (!$this->isNoMoreDecimalPlacesThan($value, TaxationSettingsProvider::CALCULATION_SCALE)) {
            $this->context->addViolation($constraint->taxRateToManyDecimalPlaces);
        }
    }

    /**
     * @param float $value
     * @param int $decimalPlaces
     * @return bool
     */
    private function isNoMoreDecimalPlacesThan($value, $decimalPlaces)
    {
        $formattedValue = rtrim(number_format($value, TaxType::TAX_RATE_FIELD_PRECISION, '.', ''), '0');

        $decimalLength = strlen(
            substr(
                strrchr($formattedValue, '.'),
                1
            )
        );

        return $decimalLength <= $decimalPlaces;
    }
}
