<?php

namespace OroB2B\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;

class ProductPriceAllowedUnitsValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        /** @var ProductPrice $value */
        $priceProduct = $value->getProduct();
        $priceUnit = $value->getUnit();

        if (!$priceProduct) {
            $this->context->addViolation(
                'orob2b.pricing.validators.product_price.not_existing_product.message'
            );

            return;
        }

        $unitPrecisions = $priceProduct->getUnitPrecisions();
        $availableUnits = [];
        foreach ($unitPrecisions as $unitPrecision) {
            $availableUnits[] = $unitPrecision->getUnit();
        }

        if (!in_array($priceUnit, $availableUnits)) {
            if ($priceUnit instanceof ProductUnit && $priceUnit->getCode()) {
                /** @var ProductPriceAllowedUnits $constraint */
                $this->context->addViolation(
                    $constraint->message,
                    [
                        '%product%' => $priceProduct->getSku(),
                        '%unit%' => $priceUnit->getCode()
                    ]
                );
            } else {
                $this->context->addViolation(
                    'orob2b.pricing.validators.product_price.not_existing_unit.message'
                );
            }
        }
    }
}
