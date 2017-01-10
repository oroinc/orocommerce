<?php

namespace Oro\Bundle\TaxBundle\Entity\Repository;

use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;

class CustomerTaxCodeRepository extends AbstractTaxCodeRepository
{
    /**
     * @param Customer $customer
     *
     * @return CustomerTaxCode|null
     */
    public function findOneByCustomer(Customer $customer)
    {
        if (!$customer->getId()) {
            return null;
        }

        return $this->findOneByEntity(TaxCodeInterface::TYPE_ACCOUNT, $customer);
    }

    /**
     * @param CustomerGroup $customerGroup
     *
     * @return CustomerTaxCode|null
     */
    public function findOneByCustomerGroup(CustomerGroup $customerGroup)
    {
        if (!$customerGroup->getId()) {
            return null;
        }

        return $this->findOneByEntity(TaxCodeInterface::TYPE_ACCOUNT_GROUP, $customerGroup);
    }
}
