<?php

namespace Oro\Bundle\CurrencyBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\CurrencyBundle\Model\OptionalPrice as Price;

class OptionalPriceValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     *
     * @param Price $price
     * @param Constraint $constraint
     */
    public function validate($price, Constraint $constraint)
    {
        if ($price->getValue() && !$price->getCurrency()) {
            $this->context->addViolationAt('currency', $constraint->message);
        }
    }
}
