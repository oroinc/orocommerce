<?php

namespace OroB2B\Bundle\TaxBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\TaxBundle\Entity\AccountGroupTaxCode;

class AccountGroupTaxCodeRepository extends EntityRepository
{
    /**
     * @param AccountGroup $accountGroup
     *
     * @return AccountGroupTaxCode|null
     */
    public function findOneByProduct(AccountGroup $accountGroup)
    {
        if (!$accountGroup->getId()) {
            return null;
        }

        return $this->createQueryBuilder('accountGroupTaxCode')
            ->where(':accountGroup MEMBER OF accountGroupTaxCode.accountGroups')
            ->setParameter('accountGroup', $accountGroup)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
