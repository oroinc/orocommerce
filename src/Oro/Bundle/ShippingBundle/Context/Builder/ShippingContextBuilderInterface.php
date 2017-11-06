<?php

namespace Oro\Bundle\ShippingBundle\Context\Builder;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;

interface ShippingContextBuilderInterface
{
    /**
     * @return ShippingContextInterface
     */
    public function getResult();

    /**
     * @param AddressInterface $shippingOrigin
     *
     * @return self
     */
    public function setShippingOrigin(AddressInterface $shippingOrigin);

    /**
     * @param ShippingLineItemCollectionInterface $lineItemCollection
     *
     * @return self
     */
    public function setLineItems(ShippingLineItemCollectionInterface $lineItemCollection);

    /**
     * @param AddressInterface $shippingAddress
     *
     * @return self
     */
    public function setShippingAddress(AddressInterface $shippingAddress);

    /**
     * @param AddressInterface $billingAddress
     *
     * @return self
     */
    public function setBillingAddress(AddressInterface $billingAddress);

    /**
     * @param string $paymentMethod
     *
     * @return self
     */
    public function setPaymentMethod($paymentMethod);

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
}
