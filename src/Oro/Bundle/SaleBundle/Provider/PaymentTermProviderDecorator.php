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

    #[\Override]
    public function getPaymentTerm(Customer $customer)
    {
        return $this->innerProvider->getPaymentTerm($customer);
    }

    #[\Override]
    public function getCurrentPaymentTerm()
    {
        return $this->innerProvider->getCurrentPaymentTerm();
    }

    #[\Override]
    public function getCustomerPaymentTerm(Customer $customer)
    {
        return $this->innerProvider->getCustomerPaymentTerm($customer);
    }

    #[\Override]
    public function getCustomerGroupPaymentTerm(CustomerGroup $customerGroup)
    {
        return $this->innerProvider->getCustomerGroupPaymentTerm($customerGroup);
    }

    #[\Override]
    public function getCustomerPaymentTermByOwner(CustomerOwnerAwareInterface $customerOwnerAware)
    {
        return $this->innerProvider->getCustomerPaymentTermByOwner($customerOwnerAware);
    }

    #[\Override]
    public function getCustomerGroupPaymentTermByOwner(CustomerOwnerAwareInterface $customerOwnerAware)
    {
        return $this->innerProvider->getCustomerGroupPaymentTermByOwner($customerOwnerAware);
    }

    #[\Override]
    public function getObjectPaymentTerm($object)
    {
        if ($object instanceof QuoteDemand) {
            return $this->innerProvider->getObjectPaymentTerm($object->getQuote());
        }

        return $this->innerProvider->getObjectPaymentTerm($object);
    }
}
