<?php

namespace Oro\Bundle\SaleBundle\Quote\Shipping\LineItem;

use Oro\Bundle\SaleBundle\Entity\Quote;

interface QuoteToShippingLineItemConverterInterface
{
    /**
     * @param Quote $quote
     *
     * @return array
     */
    public function convertLineItems(Quote $quote);
}
