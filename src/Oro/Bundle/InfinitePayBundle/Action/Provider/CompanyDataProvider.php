<?php

namespace Oro\Bundle\InfinitePayBundle\Action\Provider;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\InfinitePayBundle\Action\PropertyAccessor\CustomerPropertyAccessor;
use Oro\Bundle\InfinitePayBundle\Exception\UserDataMissingException;
use Oro\Bundle\InfinitePayBundle\Exception\ValueNotSetException;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\CompanyData;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;

class CompanyDataProvider implements CompanyDataProviderInterface
{

    /**
     * @var CustomerPropertyAccessor
     */
    protected $customerPropertyAccessor;

    public function __construct(CustomerPropertyAccessor $propertyAccessor)
    {
        $this->customerPropertyAccessor = $propertyAccessor;
    }

    /**
     * @param OrderAddress $billingAddress
     * @param Customer $customer
     * @return CompanyData
     * @throws UserDataMissingException
     */
    public function getCompanyData(OrderAddress $billingAddress, Customer $customer)
    {
        $companyData = new CompanyData();
        $companyData->setCompanyName($billingAddress->getOrganization());
        $companyData->setOwnerFsName($billingAddress->getFirstName());
        $companyData->setOwnerLsName($billingAddress->getLastName());
        try {
            $companyData->setComIdVat($this->customerPropertyAccessor->extractVatId($customer));
        } catch (ValueNotSetException $invalidArgumentException) {
            throw new UserDataMissingException("Customer does not have VAT id set.");
        }

        return $companyData;
    }
}
