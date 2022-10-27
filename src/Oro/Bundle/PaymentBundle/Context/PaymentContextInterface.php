<?php

namespace Oro\Bundle\PaymentBundle\Context;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\PaymentLineItemCollectionInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Provides an interface for payment context
 */
interface PaymentContextInterface extends CustomerOwnerAwareInterface
{
    /**
     * @return PaymentLineItemCollectionInterface
     */
    public function getLineItems();

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
     * @return string|null
     */
    public function getCurrency();

    /**
     * @return Price|null
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

    /**
     * @return Website|null
     */
    public function getWebsite();

    /**
     * @return float
     */
    public function getTotal();
}
