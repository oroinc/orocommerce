<?php

namespace Oro\Bundle\PaymentBundle\Context;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Model\ProductUnitHolderInterface;
use Oro\Bundle\ProductBundle\Model\QuantityAwareInterface;

/**
 * Interface for the payment line item model.
 *
 * @deprecated since 5.1, use directly {@see PaymentLineItem} instead
 */
interface PaymentLineItemInterface extends
    ProductUnitHolderInterface,
    ProductHolderInterface,
    QuantityAwareInterface
{
    /**
     * @return Price|null
     */
    public function getPrice();
}
