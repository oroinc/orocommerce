<?php

namespace Oro\Bundle\SaleBundle\Quote\Shipping\Context\LineItem\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\SaleBundle\Entity\QuoteProductDemand;
use Oro\Bundle\ShippingBundle\Context\LineItem\Factory\ShippingLineItemFromProductLineItemFactory;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;

/**
 * Creates:
 *  - instance of {@see ShippingLineItem} by {@see QuoteProductDemand};
 *  - collection of {@see ShippingLineItem} by iterable {@see QuoteProductDemand}.
 */
class ShippingLineItemFromQuoteProductDemandFactory extends ShippingLineItemFromProductLineItemFactory
{
    /**
     * @param QuoteProductDemand $productLineItem
     *
     * @return ShippingLineItem
     */
    public function create(ProductLineItemInterface $productLineItem): ShippingLineItem
    {
        if (!$productLineItem instanceof QuoteProductDemand) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" expected, "%s" given',
                QuoteProductDemand::class,
                get_debug_type($productLineItem)
            ));
        }

        $quoteProductOffer = $productLineItem->getQuoteProductOffer();

        $this->shippingLineItemOptionsModifier->loadShippingOptions([$quoteProductOffer]);

        $shippingLineItem = $this->createShippingLineItem($quoteProductOffer);
        $shippingLineItem->setQuantity($productLineItem->getQuantity());

        $this->shippingLineItemOptionsModifier->clear();

        return $shippingLineItem;
    }

    /**
     * @param iterable<QuoteProductDemand> $productLineItems
     *
     * @return Collection<ShippingLineItem>
     */
    public function createCollection(iterable $productLineItems): Collection
    {
        $quoteProductOffers = [];
        foreach ($productLineItems as $key => $productLineItem) {
            if (!$productLineItem instanceof QuoteProductDemand) {
                throw new \InvalidArgumentException(sprintf(
                    '"%s" expected, "%s" given',
                    QuoteProductDemand::class,
                    get_debug_type($productLineItem)
                ));
            }

            $quoteProductOffer = $productLineItem->getQuoteProductOffer();
            $quoteProductOffers[$key] = $quoteProductOffer;
        }

        $this->shippingLineItemOptionsModifier->loadShippingOptions($quoteProductOffers);

        $shippingLineItems = [];
        foreach ($quoteProductOffers as $key => $quoteProductOffer) {
            $shippingLineItem = $this->createShippingLineItem($quoteProductOffer);
            $shippingLineItem->setQuantity($productLineItems[$key]->getQuantity());

            $shippingLineItems[] = $shippingLineItem;
        }

        $this->shippingLineItemOptionsModifier->clear();

        return new ArrayCollection($shippingLineItems);
    }
}
