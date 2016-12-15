<?php

namespace Oro\Bundle\ShippingBundle\Context\Builder;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;

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
     * @param ShippingLineItemInterface $shippingLineItem
     *
     * @return self
     */
    public function addLineItem(ShippingLineItemInterface $shippingLineItem);

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
     * @param Account $customer
     *
     * @return self
     */
    public function setCustomer(Account $customer);

    /**
     * @param AccountUser $customerUser
     *
     * @return self
     */
    public function setCustomerUser(AccountUser $customerUser);
}
