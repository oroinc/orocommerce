<?php

namespace Oro\Bundle\CheckoutBundle\Factory;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Model\CheckoutLineItemConverterRegistry;

/**
 * Factory for creating checkout line items from various source types.
 *
 * Uses a converter registry to find the appropriate converter for a given source type
 * and creates checkout line item collections from that source.
 */
class CheckoutLineItemsFactory
{
    /** @var CheckoutLineItemConverterRegistry */
    protected $lineItemConverterRegistry;

    public function __construct(CheckoutLineItemConverterRegistry $lineItemConverterRegistry)
    {
        $this->lineItemConverterRegistry = $lineItemConverterRegistry;
    }

    /**
     * @param mixed $source
     *
     * @return Collection|CheckoutLineItem[]
     */
    public function create($source)
    {
        $converter = $this->lineItemConverterRegistry->getConverter($source);

        return $converter->convert($source);
    }
}
