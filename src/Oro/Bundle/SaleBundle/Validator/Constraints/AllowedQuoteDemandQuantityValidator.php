<?php

namespace Oro\Bundle\SaleBundle\Validator\Constraints;

use Oro\Bundle\SaleBundle\Entity\QuoteProductDemand;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that an offer quantity for a quote product demand is allowed.
 */
class AllowedQuoteDemandQuantityValidator extends ConstraintValidator
{
    #[\Override]
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof AllowedQuoteDemandQuantity) {
            throw new UnexpectedTypeException($constraint, AllowedQuoteDemandQuantity::class);
        }

        if (!$value instanceof QuoteProductDemand) {
            throw new UnexpectedTypeException($value, QuoteProductDemand::class);
        }

        $offer = $value->getQuoteProductOffer();
        if ($offer->isAllowIncrements()) {
            if ((float)$offer->getQuantity() > (float)$value->getQuantity()) {
                $this->context->buildViolation($constraint->lessQuantityMessage)
                    ->atPath('quantity')
                    ->addViolation();
            }
        } elseif ((float)$offer->getQuantity() !== (float)$value->getQuantity()) {
            $this->context->buildViolation($constraint->notEqualQuantityMessage)
                ->atPath('quantity')
                ->addViolation();
        }
    }
}
