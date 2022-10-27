<?php

namespace Oro\Bundle\SaleBundle\Provider;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProviderInterface;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;

/**
 * This provider allows to get PaymentTerm entity from QuoteDemand entity.
 */
class PaymentTermProviderDecorator implements PaymentTermProviderInterface
{
    /**
     * @var PaymentTermProviderInterface
     */
    protected $innerProvider;

    public function __construct(PaymentTermProviderInterface $innerProvider)
    {
        $this->innerProvider = $innerProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentTerm(Customer $customer)
    {
        return $this->innerProvider->getPaymentTerm($customer);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentPaymentTerm()
    {
        return $this->innerProvider->getCurrentPaymentTerm();
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomerPaymentTerm(Customer $customer)
    {
        return $this->innerProvider->getCustomerPaymentTerm($customer);
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomerGroupPaymentTerm(CustomerGroup $customerGroup)
    {
        return $this->innerProvider->getCustomerGroupPaymentTerm($customerGroup);
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomerPaymentTermByOwner(CustomerOwnerAwareInterface $customerOwnerAware)
    {
        return $this->innerProvider->getCustomerPaymentTermByOwner($customerOwnerAware);
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomerGroupPaymentTermByOwner(CustomerOwnerAwareInterface $customerOwnerAware)
    {
        return $this->innerProvider->getCustomerGroupPaymentTermByOwner($customerOwnerAware);
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectPaymentTerm($object)
    {
        if ($object instanceof QuoteDemand) {
            return $this->innerProvider->getObjectPaymentTerm($object->getQuote());
        }

        return $this->innerProvider->getObjectPaymentTerm($object);
    }
}
