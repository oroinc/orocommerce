<?php

namespace Oro\Bundle\ShippingBundle\Checkout;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;

interface ShippingContextInterface
{
    /**
     * @return ShippingLineItemInterface[]
     */
    public function getLineItems();

    /**
     * @return AddressInterface
     */
    public function getBillingAddress();

    /**
     * @return AddressInterface
     */
    public function getShippingAddress();

    /**
     * @return AddressInterface
     */
    public function getShippingOrigin();

    /**
     * @return String|null
     */
    public function getPaymentMethod();

    /**
     * @return String
     */
    public function getCurrency();

    /**
     * @return Price
     */
    public function getSubtotal();

}
