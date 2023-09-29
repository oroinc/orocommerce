<?php

namespace Oro\Bundle\SaleBundle\Quote\Shipping\LineItem\Converter;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;

/**
 * Interface for converters from Quote to collection of Shipping Line Items.
 */
interface QuoteToShippingLineItemConverterInterface
{
    /**
     * @param Quote $quote
     *
     * @return Collection<ShippingLineItem>
     */
    public function convertLineItems(Quote $quote): Collection;
}
