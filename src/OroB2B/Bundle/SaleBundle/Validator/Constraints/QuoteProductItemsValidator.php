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
        /** @var $quoteProductItems array|Collection|QuoteProductItem[] */
        if (!is_array($quoteProductItems) && !$quoteProductItems instanceof Collection) {
            throw new UnexpectedTypeException($quoteProductItems, 'Doctrine\Common\Collections\Collection|array');
        }
        if (empty($quoteProductItems)) {
            return;
        }
        /** @var $product Product */
        $product = null;
        $allowedUnits = [];
        foreach ($quoteProductItems as $key => $quoteProductItem) {
            if ($quoteProductItem && !$quoteProductItem instanceof QuoteProductItem) {
                throw new UnexpectedTypeException(
                    $quoteProductItem,
                    'OroB2B\Bundle\SaleBundle\Entity\QuoteProductItem'
                );
            }
            if ($quoteProductItem && !$product) {
                $product = $quoteProductItem->getQuoteProduct()->getProduct();
                $allowedUnits = $product ? $product->getAvailableUnitCodes() : [];
            }
            if (!in_array($quoteProductItem->getProductUnit()->getCode(), $allowedUnits)) {
                /** @var $constraint QuoteProductItems */
                $this->context->addViolationAt("[{$key}].productUnit", $constraint->message);
            }
        }
    }
}
