<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validator for ensuring product prices use allowed product units.
 *
 * Validates that product prices are only created for units that are configured for the associated product.
 */
class ProductPriceAllowedUnitsValidator extends ConstraintValidator
{
    /**
     * @param ProductPrice|object $value
     * @param ProductPriceAllowedUnits $constraint
     *
     */
    #[\Override]
    public function validate($value, Constraint $constraint)
    {
        $priceProduct = $value->getProduct();
        $priceUnit = $value->getUnit();

        if (!$priceProduct) {
            $this->context->buildViolation($constraint->notExistingProductMessage)
                ->atPath('product')
                ->addViolation();

            return;
        }

        $unitPrecisions = $priceProduct->getUnitPrecisions();
        $availableUnits = [];
        foreach ($unitPrecisions as $unitPrecision) {
            $availableUnits[] = $unitPrecision->getUnit();
        }

        if (!in_array($priceUnit, $availableUnits)) {
            if ($priceUnit instanceof ProductUnit && $priceUnit->getCode()) {
                $this->context->buildViolation($constraint->notAllowedUnitMessage)
                    ->atPath('unit')
                    ->setParameters([
                        '%product%' => $priceProduct->getSku(),
                        '%unit%' => $priceUnit->getCode()
                    ])
                    ->addViolation();
            } else {
                $this->context->buildViolation($constraint->notExistingUnitMessage)
                    ->atPath('unit')
                    ->addViolation();
            }
        }
    }
}
