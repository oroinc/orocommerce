<?php

namespace Oro\Bundle\SaleBundle\Quote\Shipping\LineItem\Converter;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;

interface QuoteToShippingLineItemConverterInterface
{
    /**
     * @param Quote $quote
     *
     * @return ShippingLineItemInterface[]
     */
    public function convertLineItems(Quote $quote);
}
