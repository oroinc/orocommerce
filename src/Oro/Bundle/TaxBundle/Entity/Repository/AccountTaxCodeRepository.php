<?php

namespace Oro\Bundle\TaxBundle\Entity\Repository;

use Oro\Bundle\TaxBundle\Entity\AccountTaxCode;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;

class AccountTaxCodeRepository extends AbstractTaxCodeRepository
{
    /**
     * @param Customer $account
     *
     * @return AccountTaxCode|null
     */
    public function findOneByAccount(Customer $account)
    {
        if (!$account->getId()) {
            return null;
        }

        return $this->findOneByEntity(TaxCodeInterface::TYPE_ACCOUNT, $account);
    }

    /**
     * @param CustomerGroup $accountGroup
     *
     * @return AccountTaxCode|null
     */
    public function findOneByAccountGroup(CustomerGroup $accountGroup)
    {
        if (!$accountGroup->getId()) {
            return null;
        }

        return $this->findOneByEntity(TaxCodeInterface::TYPE_ACCOUNT_GROUP, $accountGroup);
    }
}
