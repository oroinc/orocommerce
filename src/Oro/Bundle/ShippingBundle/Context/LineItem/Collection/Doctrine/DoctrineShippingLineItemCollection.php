<?php

namespace Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;

/**
 * Represents a collection of shipping line items.
 *
 * @deprecated since 5.1, Doctrine {@see ArrayCollection} is used instead
 */
class DoctrineShippingLineItemCollection extends ArrayCollection implements ShippingLineItemCollectionInterface
{
    /**
     * @param array|ShippingLineItemInterface[] $elements
     */
    public function __construct(array $elements)
    {
        parent::__construct($elements);
    }
}
