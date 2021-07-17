<?php

namespace Oro\Bundle\CheckoutBundle\Factory;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Model\CheckoutLineItemConverterRegistry;

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
