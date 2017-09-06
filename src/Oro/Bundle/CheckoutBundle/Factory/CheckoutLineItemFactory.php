<?php

namespace Oro\Bundle\CheckoutBundle\Factory;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Model\CheckoutLineItemConverterRegistry;

class CheckoutLineItemFactory
{
    /** @var CheckoutLineItemConverterRegistry */
    protected $lineItemConverterRegistry;

    /**
     * @param CheckoutLineItemConverterRegistry $lineItemConverterRegistry
     */
    public function __construct(CheckoutLineItemConverterRegistry $lineItemConverterRegistry)
    {
        $this->lineItemConverterRegistry = $lineItemConverterRegistry;
    }

    /**
     * @param mixed $source
     *
     * @return array|CheckoutLineItem[]
     */
    public function create($source)
    {
        $converter = $this->lineItemConverterRegistry->getConverter($source);

        return $converter->convert($source);
    }
}
