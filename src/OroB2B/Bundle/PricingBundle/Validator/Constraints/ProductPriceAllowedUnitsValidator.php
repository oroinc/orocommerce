<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;

class ProductPriceAllowedUnitsValidator extends ConstraintValidator
{
    /**
     * @param ProductPrice|object $value
     * @param ProductPriceAllowedUnits $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        $priceProduct = $value->getProduct();
        $priceUnit = $value->getUnit();

        if (!$priceProduct) {
            $this->context->addViolationAt('product', $constraint->notExistingProductMessage);

            return;
        }

        $unitPrecisions = $priceProduct->getUnitPrecisions();
        $availableUnits = [];
        foreach ($unitPrecisions as $unitPrecision) {
            $availableUnits[] = $unitPrecision->getUnit();
        }

        if (!in_array($priceUnit, $availableUnits, true)) {
            if ($priceUnit instanceof ProductUnit && $priceUnit->getCode()) {
                $this->context->addViolationAt(
                    'unit',
                    $constraint->notAllowedUnitMessage,
                    [
                        '%product%' => $priceProduct->getSku(),
                        '%unit%' => $priceUnit->getCode()
                    ]
                );
            } else {
                $this->context->addViolationAt('unit', $constraint->notExistingUnitMessage);
            }
        }
    }
}
