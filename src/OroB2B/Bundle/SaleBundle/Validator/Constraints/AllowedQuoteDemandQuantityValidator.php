<?php

namespace Oro\Bundle\SaleBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\SaleBundle\Entity\QuoteProductDemand;

class AllowedQuoteDemandQuantityValidator extends ConstraintValidator
{
    /**
     * @param QuoteProductDemand $value
     * @param AllowedQuoteDemandQuantity $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        $offer = $value->getQuoteProductOffer();
        $offerQuantity = (float)$offer->getQuantity();
        $quantity = (float)$value->getQuantity();

        if ($offer->isAllowIncrements()) {
            if ($offerQuantity > $quantity) {
                $this->context->buildViolation($constraint->lessQuantityMessage)
                    ->atPath('quantity')
                    ->addViolation();
            }
        } elseif ($offerQuantity !== $quantity) {
            $this->context->buildViolation($constraint->notEqualQuantityMessage)
                ->atPath('quantity')
                ->addViolation();
        }
    }
}
