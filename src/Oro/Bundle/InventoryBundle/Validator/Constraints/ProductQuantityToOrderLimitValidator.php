<?php

namespace Oro\Bundle\InventoryBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\InventoryBundle\Migrations\Schema\v1_0\AddQuantityToOrderFields;
use Oro\Bundle\InventoryBundle\Validator\QuantityToOrderValidatorService;

class ProductQuantityToOrderLimitValidator extends ConstraintValidator
{
    /**
     * @var QuantityToOrderValidatorService
     */
    private $validatorService;

    /**
     * @param QuantityToOrderValidatorService $validatorService
     */
    public function __construct(QuantityToOrderValidatorService $validatorService)
    {
        $this->validatorService = $validatorService;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof Product) {
            return;
        }

        if ($this->validatorService->isMaxLimitLowerThenMinLimit($value)) {
            $this->context->buildViolation($constraint->message)
                ->atPath(AddQuantityToOrderFields::FIELD_MINIMUM_QUANTITY_TO_ORDER)
                ->addViolation();
        }
    }
}
