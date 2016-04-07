<?php

namespace OroB2B\Bundle\TaxBundle\Entity\Repository;

use OroB2B\Bundle\TaxBundle\Entity\AccountTaxCode;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\TaxBundle\Model\TaxCodeInterface;

class AccountTaxCodeRepository extends AbstractTaxCodeRepository
{
    /**
     * @param Account $account
     *
     * @return AccountTaxCode|null
     */
    public function findOneByAccount(Account $account)
    {
        if (!$account->getId()) {
            return null;
        }

        return $this->findOneByEntity(TaxCodeInterface::TYPE_ACCOUNT, $account);
    }

    /**
     * @param AccountGroup $accountGroup
     *
     * @return AccountTaxCode|null
     */
    public function findOneByAccountGroup(AccountGroup $accountGroup)
    {
        if (!$accountGroup->getId()) {
            return null;
        }

        return $this->findOneByEntity(TaxCodeInterface::TYPE_ACCOUNT_GROUP, $accountGroup);
    }
}
