<?php

namespace Oro\Bundle\CheckoutBundle\Converter;

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

            $checkoutLineItem = new CheckoutLineItem();
            $checkoutLineItem
                ->setFromExternalSource(true)
                ->setPriceFixed(true)
                ->setProduct($quoteProductOffer->getProduct())
                ->setParentProduct($quoteProductOffer->getParentProduct())
                ->setFreeFormProduct($quoteProductOffer->getQuoteProduct()->getFreeFormProduct())
                ->setProductSku($quoteProductOffer->getProductSku())
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
