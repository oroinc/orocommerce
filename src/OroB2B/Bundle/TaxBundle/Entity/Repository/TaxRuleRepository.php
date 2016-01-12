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
     * @param string $zipCode
     * @param Region $region
     * @param string $regionText
     * @param Country $country
     * @return array
     */
    public function findByZipCode($zipCode, Region $region = null, $regionText = null, Country $country = null)
    {
        $qb = $this->createQueryBuilder('taxRule');
        $qb
            ->join('taxRule.taxJurisdiction', 'taxJurisdiction')
            ->leftJoin('taxJurisdiction.zipCodes', 'zipCodes')
            ->where($qb->expr()->eq('taxJurisdiction.country', ':country'));

        if ($region) {
            $qb
                ->andWhere($qb->expr()->eq('taxJurisdiction.region', ':region'))
                ->setParameters(
                    [
                        'country' => $region->getCountry(),
                        'region' => $region,
                    ]
                );
        } elseif ($country && $regionText) {
            $qb
                ->andWhere($qb->expr()->eq('taxJurisdiction.regionText', ':regionText'))
                ->setParameters(
                    [
                        'regionText' => $regionText,
                        'country' => $country,
                    ]
                );
        } else {
            throw new \InvalidArgumentException('You should pass only region or region text and country');
        }

        $qb
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->gte('zipCodes.zipRangeStart', $zipCode),
                    $qb->expr()->lte('zipCodes.zipRangeEnd', $zipCode),
                    $qb->expr()->eq('zipCodes.zipCode', $zipCode)
                )
            );

        return $qb->getQuery()->getResult();
    }
}
