<?php

namespace Oro\Bundle\CheckoutBundle\Model;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;

/**
 * Should be used by services which can convert given source to CheckoutLineItems array
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
