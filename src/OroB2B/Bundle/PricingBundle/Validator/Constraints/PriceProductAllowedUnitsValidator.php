<?php

namespace OroB2B\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;

class PriceProductAllowedUnitsValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        /** @var ProductPrice $value */
        $priceProduct = $value->getProduct();
        $priceUnit = $value->getUnit();

        $unitPrecisions = $priceProduct->getUnitPrecisions();
        $availableUnits = [];
        foreach ($unitPrecisions as $unitPrecision) {
            $availableUnits[] = $unitPrecision->getUnit();
        }

        if (!in_array($priceUnit, $availableUnits)) {
            /** @var PriceProductAllowedUnits $constraint */
            $this->context->addViolation(
                $constraint->message,
                [
                    '%product%' => $priceProduct->getSku(),
                    '%unit%' => $priceUnit->getCode()
                ]
            );
        }
    }
}
