<?php

namespace Oro\Bundle\SaleBundle\Quote\Shipping\LineItem\Converter\FirstOffers;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Quote\Shipping\LineItem\Converter\QuoteToShippingLineItemConverterInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Factory\ShippingLineItemFromProductLineItemFactoryInterface;

/**
 * Converts first QuoteProductOffers to Shipping Line Items collection.
 */
class FirstOffersQuoteToShippingLineItemConverter implements QuoteToShippingLineItemConverterInterface
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
        $offersToConvert = [];
        foreach ($quote->getQuoteProducts() as $quoteProduct) {
            $offers = $quoteProduct->getQuoteProductOffers();
            if ($offers->count() <= 0) {
                $offersToConvert = [];
                break;
            }

            $offersToConvert[] = $offers->first();
        }

        return $this->shippingLineItemFactory->createCollection($offersToConvert);
    }
}
