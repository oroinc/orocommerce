<?php

namespace OroB2B\Bundle\SaleBundle\Validator\Constraints;

use Doctrine\Common\Collections\Collection;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class QuoteProductItemsValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($quoteProductItems, Constraint $constraint)
    {
        /** @var $quoteProductItems Collection|QuoteProductItem[] */
        if (!$quoteProductItems instanceof Collection) {
            throw new UnexpectedTypeException($quoteProductItems, 'Doctrine\Common\Collections\Collection');
        }
        if (empty($quoteProductItems)) {
            return;
        }
        if (!$quoteProductItems->first() instanceof QuoteProductItem) {
            throw new UnexpectedTypeException(
                $quoteProductItems->first(),
                'OroB2B\Bundle\SaleBundle\Entity\QuoteProductItem'
            );
        }
        /** @var $product Product */
        $product = $quoteProductItems->first()->getQuoteProduct()->getProduct();
        $allowedUnits = $product ? $product->getAvailableUnitCodes() : [];
        foreach ($quoteProductItems as $key => $quoteProductItem) {
            if (!in_array($quoteProductItem->getProductUnit()->getCode(), $allowedUnits)) {
                $this->context->addViolationAt("[{$key}].productUnit", $constraint->message);
            }
        }
    }
}
