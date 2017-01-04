<?php

namespace Oro\Bundle\PaymentBundle\Context\Builder;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\PaymentLineItemCollectionInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface;

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
