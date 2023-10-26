<?php

namespace Oro\Bundle\SaleBundle\Quote\Shipping\LineItem\Converter;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;

/**
 * Interface for converters from Quote to collection of Shipping Line Items.
 */
interface QuoteToShippingLineItemConverterInterface
{
    /**
     * @param Quote $quote
     *
     * @return ShippingLineItemCollectionInterface
     */
    public function convertLineItems(Quote $quote);
}
