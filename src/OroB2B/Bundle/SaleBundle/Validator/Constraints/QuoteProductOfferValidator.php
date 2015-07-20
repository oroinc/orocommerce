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
            $this->addViolation($constraint);
            return;
        }

        if ($quoteProduct->isTypeNotAvailable()) {
            $product = $quoteProduct->getProductReplacement();
        } else {
            $product = $quoteProduct->getProduct();
        }

        if (null === $product) {
            $this->addViolation($constraint);
            return;
        }

        if ([] === ($allowedUnits = $product->getAvailableUnitCodes())) {
            $this->addViolation($constraint);
            return;
        }

        if (null === ($productUnit = $quoteProductOffer->getProductUnit())) {
            $this->addViolation($constraint);
            return;
        }

        if (!in_array($productUnit->getCode(), $allowedUnits, true)) {
            $this->addViolation($constraint);
            return;
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
