<?php

namespace Oro\Bundle\SaleBundle\Validator\Constraints;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that a quote is acceptable.
 */
class QuoteAcceptableValidator extends ConstraintValidator
{
    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof QuoteAcceptable) {
            throw new UnexpectedTypeException($constraint, QuoteAcceptable::class);
        }

        if (null === $value) {
            return;
        }

        if ($value instanceof CheckoutSource) {
            $value = $value->getEntity();
        }
        if ($value instanceof QuoteDemand) {
            $value = $value->getQuote();
        }

        $isAcceptable = $value instanceof Quote ? $value->isAcceptable() : $constraint->default;
        if (!$isAcceptable) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('%qid%', (int)$value?->getQid())
                ->setCode(QuoteAcceptable::CODE)
                ->addViolation();
        }
    }
}
