<?php

namespace OroB2B\Bundle\SaleBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\SaleBundle\Validator\Constraints;
use OroB2B\Bundle\SaleBundle\Entity;

class QuoteProductItemValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     *
     * @param Entity\QuoteProductItem $quoteProductItem
     * @param Constraints\QuoteProductItem $constraint
     * @throws UnexpectedTypeException
     */
    public function validate($quoteProductItem, Constraint $constraint)
    {
        if (!$quoteProductItem instanceof Entity\QuoteProductItem) {
            throw new UnexpectedTypeException(
                $quoteProductItem,
                'OroB2B\Bundle\SaleBundle\Entity\QuoteProductItem'
            );
        }
        /* @var $product Product */
        $product = $quoteProductItem->getQuoteProduct()->getProduct();
        $allowedUnits = $product ? $product->getAvailableUnitCodes() : [];
        if (!in_array($quoteProductItem->getProductUnit()->getCode(), $allowedUnits)) {
            /* @var $constraint Constraints\QuoteProductItem */
            $this->context->addViolationAt('productUnit', $constraint->message);
        }
    }
}
