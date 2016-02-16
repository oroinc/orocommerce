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
     * @param Region|null $region
     * @param null $regionText
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

        } elseif ($regionText) {
            $qb->andWhere($qb->expr()->eq('tax_jurisdiction.regionText', ':region_text'))
                ->setParameter('region_text', $regionText);
        } else {
            throw new \InvalidArgumentException('Region or Region Text arguments missed');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find TaxRules by ZipCode (with Region/Country check)
     *
     * @param string $zipCode
     * @param Country $country
     * @param Region $region
     * @param string $regionText
     * @return TaxRule[]
     */
    public function findByZipCode($zipCode, Country $country, Region $region = null, $regionText = null)
    {
        $qb = $this->createQueryBuilder('taxRule');
        $qb
            ->join('taxRule.taxJurisdiction', 'taxJurisdiction')
            ->leftJoin('taxJurisdiction.zipCodes', 'zipCodes')
            ->where($qb->expr()->eq('taxJurisdiction.country', ':country'))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()->lte('CAST(zipCodes.zipRangeStart as int)', ':zipCodeForRange'),
                        $qb->expr()->gte('CAST(zipCodes.zipRangeEnd as int)', ':zipCodeForRange')
                    ),
                    $qb->expr()->eq('zipCodes.zipCode', ':zipCode')
                )
            )
            ->setParameters([
                'country' => $country,
                'zipCode' => $zipCode,
                'zipCodeForRange' => (int)$zipCode
            ]);

        if ($region) {
            $qb
                ->andWhere($qb->expr()->eq('taxJurisdiction.region', ':region'))
                ->setParameter('region', $region);
        } elseif ($regionText) {
            $qb
                ->andWhere($qb->expr()->eq('taxJurisdiction.regionText', ':regionText'))
                ->setParameter('regionText', $regionText);

        } else {
            throw new \InvalidArgumentException('You should pass only region or region text and country');
        }

        return $qb->getQuery()->getResult();
    }
}
