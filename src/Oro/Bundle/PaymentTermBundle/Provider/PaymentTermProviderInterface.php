<?php

namespace Oro\Bundle\PaymentTermBundle\Provider;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;

/**
 * The interface that describes methods for retrieving the PaymentTerm entity from different sources.
 */
interface PaymentTermProviderInterface
{
    /**
     * @param Customer $customer
     * @return PaymentTerm|null
     */
    public function getPaymentTerm(Customer $customer);

    /**
     * @return PaymentTerm|null
     */
    public function getCurrentPaymentTerm();

    /**
     * @param Customer $customer
     * @return PaymentTerm|null
     */
    public function getCustomerPaymentTerm(Customer $customer);

    /**
     * @param CustomerGroup $customerGroup
     * @return PaymentTerm|null
     */
    public function getCustomerGroupPaymentTerm(CustomerGroup $customerGroup);

    /**
     * @param CustomerOwnerAwareInterface $customerOwnerAware
     * @return PaymentTerm|null
     */
    public function getCustomerPaymentTermByOwner(CustomerOwnerAwareInterface $customerOwnerAware);

    /**
     * @param CustomerOwnerAwareInterface $customerOwnerAware
     * @return PaymentTerm|null
     */
    public function getCustomerGroupPaymentTermByOwner(CustomerOwnerAwareInterface $customerOwnerAware);

    /**
     * @param object $object
     * @return null|PaymentTerm
     * @throws \InvalidArgumentException if argument is not an object
     */
    public function getObjectPaymentTerm($object);
}
