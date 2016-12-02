<?php

namespace Oro\Bundle\SaleBundle\Factory;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProductDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Factory\ShippingContextFactory;

class QuoteShippingContextFactory
{
    /** @var ShippingContextFactory|null $shippingContextFactory */
    private $shippingContextFactory;

    /**
     * @param ShippingContextFactory $shippingContextFactory
     */
    public function setShippingContextFactory(ShippingContextFactory $shippingContextFactory)
    {
        $this->shippingContextFactory = $shippingContextFactory;
    }

    /**
     * @param Quote $quote
     *
     * @return null|ShippingContext
     */
    public function create(Quote $quote)
    {
        if (!$this->shippingContextFactory) {
            return null;
        }

        $shippingContext = $this->shippingContextFactory->create();

        $shippingContext->setShippingAddress($quote->getShippingAddress());
        $shippingContext->setCurrency($quote->getCurrency());
        $shippingContext->setSourceEntity($quote);
        $shippingContext->setSourceEntityIdentifier($quote->getId());

        if (!$quote->getDemands()->isEmpty()) {
            $shippingContext->setLineItemsByData(
                $this->getShippingLineItemsForQuote($quote)
            );
        }

        return $shippingContext;
    }

    /**
     * @param Quote $quote
     *
     * @return array|ShippingLineItem[]
     */
    private function getShippingLineItemsForQuote(Quote $quote)
    {
        $shippingLineItems = [];

        foreach ($quote->getDemands() as $demand) {
            foreach ($demand->getDemandProducts() as $demandProduct) {
                $productOffer = $demandProduct->getQuoteProductOffer();

                $shippingLineItems[] = $this->createShippingLineItem($demandProduct, $productOffer);
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
