<?php

declare(strict_types=1);

namespace Oro\Bundle\InventoryBundle\Validator\Constraints;

use Oro\Bundle\InventoryBundle\Validator\UpcomingLabelCheckoutLineItemValidator;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Constraint validator that checks for a product line item is upcoming.
 */
class IsUpcomingValidator extends ConstraintValidator
{
    private UpcomingLabelCheckoutLineItemValidator $upcomingLabelCheckoutLineItemValidator;

    public function __construct(UpcomingLabelCheckoutLineItemValidator $upcomingLabelCheckoutLineItemValidator)
    {
        $this->upcomingLabelCheckoutLineItemValidator = $upcomingLabelCheckoutLineItemValidator;
    }

    #[\Override]
    public function validate($value, Constraint $constraint): void
    {
        if ($value === null) {
            return;
        }

        if (!$value instanceof ProductLineItemInterface) {
            throw new UnexpectedValueException($value, ProductLineItemInterface::class);
        }

        if (!$constraint instanceof IsUpcoming) {
            throw new UnexpectedTypeException($constraint, IsUpcoming::class);
        }

        $message = $this->upcomingLabelCheckoutLineItemValidator->getMessageIfUpcoming($value);
        if ($message !== null) {
            $this->context
                ->buildViolation($constraint->message ?: $message)
                ->setParameter('{{ product_sku }}', $value->getProduct()->getSku())
                ->atPath('product')
                ->setCause($value)
                ->setCode(IsUpcoming::IS_UPCOMING)
                ->addViolation();
        }
    }
}
