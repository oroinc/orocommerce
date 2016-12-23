<?php

namespace Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;

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
