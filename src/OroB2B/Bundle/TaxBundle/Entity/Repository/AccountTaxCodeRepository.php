<?php

namespace OroB2B\Bundle\TaxBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\TaxBundle\Entity\AccountTaxCode;
use OroB2B\Bundle\AccountBundle\Entity\Account;

class AccountTaxCodeRepository extends EntityRepository
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
}
