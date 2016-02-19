<?php

namespace OroB2B\Bundle\TaxBundle\Entity\Repository;

use OroB2B\Bundle\TaxBundle\Entity\AccountTaxCode;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;

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

        return $this->createQueryBuilder('accountTaxCode')
            ->where(':account MEMBER OF accountTaxCode.accounts')
            ->setParameter('account', $account)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
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

        return $this->createQueryBuilder('accountTaxCode')
            ->where(':accountGroup MEMBER OF accountTaxCode.accountGroups')
            ->setParameter('accountGroup', $accountGroup)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
