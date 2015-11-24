<?php

namespace OroB2B\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContext;

use OroB2B\Bundle\PricingBundle\Entity\PriceListAwareInterface;

class UniquePriceListValidator extends ConstraintValidator
{
    /**
     * Checks if the passed value is valid.
     *
     * @param PriceListAwareInterface[] $value The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     */
    public function validate($value, Constraint $constraint)
    {
        /** @var ExecutionContext $context */
        $context = $this->context;

        $ids = [];
        $i = 0;
        foreach ($value as $priceListAware) {
            if (!$priceListAware instanceof PriceListAwareInterface) {
                throw new \InvalidArgumentException();
            }

            if (!$priceListAware->getPriceList()) {
                continue;
            }
            $id = $priceListAware->getPriceList()->getId();
            if (in_array($id, $ids, true)) {
                $context->buildViolation($constraint->message, [])
                    ->atPath("[$i].priceList")
                    ->addViolation();
            }
            $ids[] = $id;
            $i++;
        }
    }
}
