<?php

namespace Oro\Bundle\PromotionBundle\Context;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;

interface PromotionContextInterface extends CustomerOwnerAwareInterface
{
    /**
     * @return AddressInterface|null
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
     * @return string|null
     */
    public function getShippingMethod();

    /**
     * @return string
     */
    public function getCurrency();

    /**
     * @return Price
     */
    public function getSubtotal();

    /**
     * @return object
     */
    public function getSourceEntity();

    /**
     * @return mixed
     */
    public function getSourceEntityIdentifier();
}
