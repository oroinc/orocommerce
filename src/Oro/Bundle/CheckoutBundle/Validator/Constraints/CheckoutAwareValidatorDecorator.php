<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Validator\Constraints;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutWorkflowHelper;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderAwareInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ValidatorException;

/**
 * Decorates a constraint validator that works with {@see Checkout} or {@see CheckoutLineItem} to add the ability
 * to restrict it by the specified checkout steps.
 * Allowed checkout steps are expected to be specified in the constraint payload - in "checkoutSteps" array.
 * Expected validation value is either a {@see Checkout} or {@see ProductLineItemsHolderAwareInterface}
 * with {@see Checkout} as a line items holder.
 */
class CheckoutAwareValidatorDecorator extends ConstraintValidator
{
    public function __construct(
        private readonly ConstraintValidator $innerValidator,
        private readonly CheckoutWorkflowHelper $checkoutWorkflowHelper
    ) {
    }

    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (null === $value) {
            return;
        }

        $checkoutSteps = $constraint->payload['checkoutSteps'] ?? [];
        if ($checkoutSteps) {
            if (!\is_array($checkoutSteps)) {
                throw new ValidatorException(\sprintf(
                    'The constraint payload option "checkoutSteps" is expected to be of type "array", '
                    . 'but is of type "%s".',
                    get_debug_type($checkoutSteps)
                ));
            }

            $entity = $value;
            if ($value instanceof ProductLineItemsHolderAwareInterface) {
                $entity = $value->getLineItemsHolder();
            }

            if ($entity instanceof Checkout) {
                $workflowItem = $this->checkoutWorkflowHelper->getWorkflowItem($entity);
                if ($workflowItem && !\in_array($workflowItem->getCurrentStep()?->getName(), $checkoutSteps, true)) {
                    return;
                }
            }
        }

        $this->innerValidator->initialize($this->context);
        $this->innerValidator->validate($value, $constraint);
    }
}
