<?php

namespace Oro\Bundle\SaleBundle\Quote\Shipping\LineItem\Converter\SelectedOffers;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Quote\Shipping\LineItem\Converter\QuoteToShippingLineItemConverterInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Factory\ShippingLineItemFromProductLineItemFactoryInterface;

/**
 * Converts selected QuoteProductDemands to Shipping Line Items collection.
 */
class SelectedOffersQuoteToShippingLineItemConverter implements QuoteToShippingLineItemConverterInterface
{
    private ShippingLineItemFromProductLineItemFactoryInterface $shippingLineItemFactory;

    public function __construct(
        ShippingLineItemFromProductLineItemFactoryInterface $shippingLineItemFactory
    ) {
        $this->shippingLineItemFactory = $shippingLineItemFactory;
    }

    #[\Override]
    public function convertLineItems(Quote $quote): Collection
    {
        $quoteProductDemandsToConvert = [];
        foreach ($quote->getDemands() as $demand) {
            foreach ($demand->getDemandProducts() as $productDemand) {
                $quoteProductDemandsToConvert[] = $productDemand;
            }
        }

        return $this->shippingLineItemFactory->createCollection($quoteProductDemandsToConvert);
    }
}
