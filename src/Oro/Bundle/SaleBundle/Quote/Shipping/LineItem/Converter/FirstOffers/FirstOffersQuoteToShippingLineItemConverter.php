<?php

namespace Oro\Bundle\SaleBundle\Quote\Shipping\LineItem\Converter\FirstOffers;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Quote\Shipping\LineItem\Converter\QuoteToShippingLineItemConverterInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;

class FirstOffersQuoteToShippingLineItemConverter implements QuoteToShippingLineItemConverterInterface
{
    /**
     * [@inheritdoc}
     */
    public function convertLineItems(Quote $quote)
    {
        $lineItems = [];

        foreach ($quote->getQuoteProducts() as $quoteProduct) {
            if ($quoteProduct->getQuoteProductOffers()->count() <= 0) {
                continue;
            }

            $firstQuoteProductOffer = $quoteProduct->getQuoteProductOffers()[0];

            $lineItems[] = $this->createShippingLineItem($firstQuoteProductOffer);
        }

        return $lineItems;
    }

    /**
     * @param QuoteProductOffer  $productOffer
     *
     * @return ShippingLineItem
     */
    private function createShippingLineItem(QuoteProductOffer $productOffer)
    {
        $shippingLineItem = new ShippingLineItem();

        $shippingLineItem->setProduct($productOffer->getProduct());
        $shippingLineItem->setProductHolder($productOffer);
        $shippingLineItem->setProductUnit($productOffer->getProductUnit());
        $shippingLineItem->setQuantity($productOffer->getQuantity());
        $shippingLineItem->setPrice($productOffer->getPrice());

        return $shippingLineItem;
    }
}
