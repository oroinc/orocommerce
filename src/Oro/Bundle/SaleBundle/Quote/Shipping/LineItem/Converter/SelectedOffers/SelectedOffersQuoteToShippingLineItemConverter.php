<?php

namespace Oro\Bundle\SaleBundle\Quote\Shipping\LineItem\Converter\SelectedOffers;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProductDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Quote\Shipping\LineItem\Converter\QuoteToShippingLineItemConverterInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;

class SelectedOffersQuoteToShippingLineItemConverter implements QuoteToShippingLineItemConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function convertLineItems(Quote $quote)
    {
        $shippingLineItems = [];

        foreach ($quote->getDemands() as $demand) {
            foreach ($demand->getDemandProducts() as $productDemand) {
                $productOffer = $productDemand->getQuoteProductOffer();

                $shippingLineItems[] = $this->createShippingLineItem($productDemand, $productOffer);
            }
        }

        return $shippingLineItems;
    }

    /**
     * @param QuoteProductDemand $demand
     * @param QuoteProductOffer  $productOffer
     *
     * @return ShippingLineItem
     */
    private function createShippingLineItem(QuoteProductDemand $demand, QuoteProductOffer $productOffer)
    {
        $shippingLineItem = new ShippingLineItem();

        $shippingLineItem->setProduct($productOffer->getProduct());
        $shippingLineItem->setProductHolder($productOffer);
        $shippingLineItem->setProductUnit($productOffer->getProductUnit());
        $shippingLineItem->setQuantity($demand->getQuantity());
        $shippingLineItem->setPrice($productOffer->getPrice());

        return $shippingLineItem;
    }
}
