<?php

namespace OroB2B\Bundle\SaleBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\SaleBundle\Validator\Constraints;
use OroB2B\Bundle\SaleBundle\Entity;

class QuoteProductOfferValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     *
     * @param Entity\QuoteProductOffer $quoteProductOffer
     * @param Constraints\QuoteProductOffer $constraint
     * @throws UnexpectedTypeException
     */
    public function validate($quoteProductOffer, Constraint $constraint)
    {
        if (!$quoteProductOffer instanceof Entity\QuoteProductOffer) {
            throw new UnexpectedTypeException(
                $quoteProductOffer,
                'OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer'
            );
        }
        /* @var $product Product */
        $product = $quoteProductOffer->getQuoteProduct()->getProduct();
        $allowedUnits = $product ? $product->getAvailableUnitCodes() : [];
        if (!in_array($quoteProductOffer->getProductUnit()->getCode(), $allowedUnits, true)) {
            /* @var $constraint Constraints\QuoteProductOffer */
            $this->context->addViolationAt('productUnit', $constraint->message);
        }
    }
}
