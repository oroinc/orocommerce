<?php

namespace OroB2B\Bundle\SaleBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use OroB2B\Bundle\SaleBundle\Validator\Constraints;
use OroB2B\Bundle\SaleBundle\Entity;

class QuoteProductOfferValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     *
     * @param Entity\QuoteProductOffer $quoteProductOffer
     * @param Constraints\QuoteProductOffer $constraint
     */
    public function validate($quoteProductOffer, Constraint $constraint)
    {
        if (!$quoteProductOffer instanceof Entity\QuoteProductOffer) {
            throw new UnexpectedTypeException(
                $quoteProductOffer,
                'OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer'
            );
        }

        if (null === ($quoteProduct = $quoteProductOffer->getQuoteProduct())) {
            return $this->addViolation($constraint);
        }

        if (null === ($product = $quoteProduct->getProduct())) {
            return $this->addViolation($constraint);
        }

        if ([] === ($allowedUnits = $product->getAvailableUnitCodes())) {
            return $this->addViolation($constraint);
        }

        if (null === ($productUnit = $quoteProductOffer->getProductUnit())) {
            return $this->addViolation($constraint);
        }

        if (!in_array($productUnit->getCode(), $allowedUnits, true)) {
            return $this->addViolation($constraint);
        }
    }

    /**
     * @param Constraints\QuoteProductOffer $constraint
     */
    protected function addViolation(Constraints\QuoteProductOffer $constraint)
    {
        $this->context->addViolationAt('productUnit', $constraint->message);
    }

}
