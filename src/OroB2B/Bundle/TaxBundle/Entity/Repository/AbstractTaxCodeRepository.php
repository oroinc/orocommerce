<?php

namespace OroB2B\Bundle\TaxBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\TaxBundle\Entity\AbstractTaxCode;

abstract class AbstractTaxCodeRepository extends EntityRepository
{
    /**
     * @param array $codes
     * @return AbstractTaxCode[]
     */
    public function findByCodes(array $codes = [])
    {
        $qb = $this->createQueryBuilder('taxCode');

        return $qb
            ->where($qb->expr()->in('taxCode.code', ':codes'))
            ->setParameter('codes', $codes)
            ->getQuery()
            ->getResult();
    }
}
