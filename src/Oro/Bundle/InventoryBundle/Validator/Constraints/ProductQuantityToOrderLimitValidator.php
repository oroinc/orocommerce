<?php

namespace Oro\Bundle\InventoryBundle\Validator\Constraints;

use Oro\Bundle\InventoryBundle\Model\Inventory;
use Oro\Bundle\InventoryBundle\Validator\QuantityToOrderValidatorService;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ProductQuantityToOrderLimitValidator extends ConstraintValidator
{
    /**
     * @var QuantityToOrderValidatorService
     */
    private $validatorService;

    public function __construct(QuantityToOrderValidatorService $validatorService)
    {
        $this->validatorService = $validatorService;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof Product || !$value->getId()) {
            return;
        }

        if ($this->validatorService->isMaxLimitLowerThenMinLimit($value)) {
            $this->context->buildViolation($constraint->message)
                ->atPath(Inventory::FIELD_MINIMUM_QUANTITY_TO_ORDER)
                ->addViolation();
        }
    }
}
