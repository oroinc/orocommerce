<?php

namespace OroB2B\Bundle\TaxBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

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

    /**
     * Find TaxRules by Country
     *
     * @param Country $country
     * @param Region|null  $region
     * @param null    $regionText
     * @return TaxRule[]
     */
    public function findByCountryAndRegion(Country $country, Region $region = null, $regionText = null)
    {
        $qb = $this->createQueryBuilder('tax_rule');
        $qb->leftJoin('tax_rule.taxJurisdiction', 'tax_jurisdiction')
            ->leftJoin('tax_jurisdiction.zipCodes', 'zip_codes')
            ->where($qb->expr()->eq('tax_jurisdiction.country', ':country'))
            ->andWhere($qb->expr()->isNull('zip_codes.id'))
            ->setParameter('country', $country);
        if ($region) {
            $qb->andWhere($qb->expr()->eq('tax_jurisdiction.region', ':region'))
                ->setParameter('region', $region);

        } else {
            $qb->andWhere($qb->expr()->eq('tax_jurisdiction.regionText', ':region_text'))
                ->setParameter('region_text', $regionText);
        }

        return $qb->getQuery()->getResult();
    }
}
