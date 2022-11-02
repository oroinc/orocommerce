<?php

namespace Oro\Bundle\PaymentBundle\Context\Builder;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\PaymentLineItemCollectionInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Provides an interface for payment context builder
 */
interface PaymentContextBuilderInterface
{
    /**
     * @return PaymentContextInterface
     */
    public function getResult();

    /**
     * @param PaymentLineItemCollectionInterface $lineItemCollection
     *
     * @return self
     */
    public function setLineItems(PaymentLineItemCollectionInterface $lineItemCollection);

    /**
     * @param PaymentLineItemInterface $paymentLineItem
     *
     * @return self
     */
    public function addLineItem(PaymentLineItemInterface $paymentLineItem);

    /**
     * @param AddressInterface $shippingAddress
     *
     * @return self
     */
    public function setShippingAddress(AddressInterface $shippingAddress);

    /**
     * @param AddressInterface $shippingOrigin
     *
     * @return self
     */
    public function setShippingOrigin(AddressInterface $shippingOrigin);

    /**
     * @param AddressInterface $billingAddress
     *
     * @return self
     */
    public function setBillingAddress(AddressInterface $billingAddress);

    /**
     * @param string $shippingMethod
     *
     * @return self
     */
    public function setShippingMethod($shippingMethod);

    /**
     * @param Customer $customer
     *
     * @return self
     */
    public function setCustomer(Customer $customer);

    /**
     * @param CustomerUser $customerUser
     *
     * @return self
     */
    public function setCustomerUser(CustomerUser $customerUser);

    /**
     * @param Price $subTotal
     *
     * @return self
     */
    public function setSubTotal(Price $subTotal);

    /**
     * @param string $currency
     *
     * @return self
     */
    public function setCurrency($currency);

    /**
     * @param Website $website
     *
     * @return self
     */
    public function setWebsite(Website $website);

    /**
     * @param float $total
     *
     * @return self
     */
    public function setTotal($total);
}
