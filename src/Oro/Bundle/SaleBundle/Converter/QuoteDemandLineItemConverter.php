<?php

namespace Oro\Bundle\SaleBundle\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Model\CheckoutLineItemConverterInterface;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;

/**
 * Converts QuoteDemand line items to CheckoutLineItems.
 */
class QuoteDemandLineItemConverter implements CheckoutLineItemConverterInterface
{
    /**
     * {@inheritDoc}
     */
    public function isSourceSupported($source)
    {
        return $source instanceof QuoteDemand;
    }

    /**
     * @param QuoteDemand $source
     * {@inheritDoc}
     */
    public function convert($source)
    {
        $lineItems = $source->getLineItems();
        $checkoutLineItems = new ArrayCollection();

        foreach ($lineItems as $lineItem) {
            $quoteProductOffer = $lineItem->getQuoteProductOffer();
            $productSku = $quoteProductOffer->getProductSku()
                ??  $quoteProductOffer->getQuoteProduct()->getProductSku();
            $freeFormProduct = !$quoteProductOffer->getQuoteProduct()->getProduct()
                ? $quoteProductOffer->getQuoteProduct()->getFreeFormProduct()
                : null;

            $checkoutLineItem = new CheckoutLineItem();
            $checkoutLineItem
                ->setFromExternalSource(true)
                ->setPriceFixed(true)
                ->setProduct($quoteProductOffer->getProduct())
                ->setParentProduct($quoteProductOffer->getParentProduct())
                ->setFreeFormProduct($freeFormProduct)
                ->setProductSku($productSku)
                ->setProductUnit($quoteProductOffer->getProductUnit())
                ->setProductUnitCode($quoteProductOffer->getProductUnitCode())
                ->setQuantity($lineItem->getQuantity())
                ->setPrice($quoteProductOffer->getPrice())
                ->setPriceType($quoteProductOffer->getPriceType())
                ->setComment($quoteProductOffer->getQuoteProduct()->getComment());
            $checkoutLineItems->add($checkoutLineItem);
        }

        return $checkoutLineItems;
    }
}
