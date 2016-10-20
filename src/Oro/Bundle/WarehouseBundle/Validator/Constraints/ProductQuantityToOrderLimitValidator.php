<?php

namespace Oro\Bundle\WarehouseBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WarehouseBundle\Migrations\Schema\v1_2\AddQuantityToOrderFields;

class ProductQuantityToOrderLimitValidator extends ConstraintValidator
{
    /**
     * @var EntityFallbackResolver
     */
    protected $fallbackResolver;

    /**
     * @param EntityFallbackResolver $fallbackResolver
     */
    public function __construct(EntityFallbackResolver $fallbackResolver)
    {
        $this->fallbackResolver = $fallbackResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof Product) {
            return;
        }

        $minValue = $this->fallbackResolver->getFallbackValue(
            $value,
            AddQuantityToOrderFields::FIELD_MINIMUM_QUANTITY_TO_ORDER
        );
        $maxValue = $this->fallbackResolver->getFallbackValue(
            $value,
            AddQuantityToOrderFields::FIELD_MAXIMUM_QUANTITY_TO_ORDER
        );
        if (is_numeric($minValue) && is_numeric($maxValue) && $maxValue < $minValue) {
            $this->context->buildViolation($constraint->message)
                ->atPath(AddQuantityToOrderFields::FIELD_MINIMUM_QUANTITY_TO_ORDER)
                ->addViolation();
        }
    }
}
