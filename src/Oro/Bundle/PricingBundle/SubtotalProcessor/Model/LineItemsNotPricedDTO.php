<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\SubtotalProcessor\Model;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * DTO to wrap line items for passing to a not-priced line items subtotal provider.
 */
class LineItemsNotPricedDTO implements LineItemsNotPricedAwareInterface
{
    /** @var Collection<ProductLineItemInterface> */
    private Collection $lineItems;

    /**
     * @param Collection<ProductLineItemInterface> $lineItems
     */
    public function __construct(Collection $lineItems)
    {
        $this->lineItems = $lineItems;
    }

    public function getLineItems(): Collection
    {
        return $this->lineItems;
    }
}
