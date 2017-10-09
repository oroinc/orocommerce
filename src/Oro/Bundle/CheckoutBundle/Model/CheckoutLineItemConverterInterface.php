<?php

namespace Oro\Bundle\CheckoutBundle\Model;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;

/**
 * Interface for converter that should create CheckoutLineItems array from given source
 */
interface CheckoutLineItemConverterInterface
{
    /**
     * @param mixed $source
     *
     * @return bool
     */
    public function isSourceSupported($source);

    /**
     * @param mixed $source
     *
     * @return Collection|CheckoutLineItem[]
     */
    public function convert($source);
}
