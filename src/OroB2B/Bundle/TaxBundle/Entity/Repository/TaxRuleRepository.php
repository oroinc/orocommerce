<?php

namespace OroB2B\Bundle\TaxBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\AddressBundle\Entity\Country;

use OroB2B\Bundle\TaxBundle\Entity\TaxRule;

class TaxRuleRepository extends EntityRepository
{
    /**
     * Find TaxRules by Country
     *
     * @param Country $country
     * @return TaxRule[]
     */
    public function findByCountry(Country $country)
    {
        $qb = $this->createQueryBuilder('taxRule');
        $qb
            ->join('taxRule.taxJurisdiction', 'taxJurisdiction')
            ->leftJoin('taxJurisdiction.zipCodes', 'zipCodes')
            ->where($qb->expr()->eq('taxJurisdiction.country', ':country'))
            ->andWhere($qb->expr()->isNull('taxJurisdiction.region'))
            ->andWhere($qb->expr()->isNull('taxJurisdiction.regionText'))
            ->andWhere($qb->expr()->isNull('zipCodes.id'))
            ->setParameter('country', $country);

        return $qb->getQuery()->getResult();
    }
}
