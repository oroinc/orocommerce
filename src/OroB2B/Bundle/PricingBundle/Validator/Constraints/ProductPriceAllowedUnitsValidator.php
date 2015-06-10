<?php

namespace OroB2B\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;

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
            $this->context->addViolationAt('price.product', $constraint->notExistingProductMessage);

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
                $this->context->addViolationAt(
                    'price.unit',
                    $constraint->notAllowedUnitMessage,
                    [
                        '%product%' => $priceProduct->getSku(),
                        '%unit%' => $priceUnit->getCode()
                    ]
                );
            } else {
                $this->context->addViolationAt('price.unit', $constraint->notExistingUnitMessage);
            }
        }
    }
}
