<?php

namespace Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\PaymentLineItemCollectionInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface;

class DoctrinePaymentLineItemCollection extends ArrayCollection implements PaymentLineItemCollectionInterface
{
    /**
     * @param array|PaymentLineItemInterface[] $elements
     */
    public function __construct(array $elements)
    {
        parent::__construct($elements);
    }
}
