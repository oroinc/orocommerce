<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\BaseTransition;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Common implementation for validation processing in validator aware workflow transitions.
 */
trait ValidationTrait
{
    protected ?ValidatorInterface $validator = null;

    public function setValidator(ValidatorInterface $validator): void
    {
        $this->validator = $validator;
    }

    protected function isValidationPassed(
        Checkout $checkout,
        string|array $validationGroups,
        ?Collection $errors = null
    ): bool {
        $violationList = $this->validator->validate($checkout, null, $validationGroups);
        if ($violationList->count() === 0) {
            return true;
        }

        if (null !== $errors) {
            foreach ($violationList as $violation) {
                $errors->add([
                    'message' => $violation->getMessageTemplate(),
                    'parameters' => $violation->getParameters()
                ]);
            }
        }

        return false;
    }
}
