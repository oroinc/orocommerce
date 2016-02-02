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
     * @param string  $productTaxCode
     * @return TaxRule[]
     */
    public function findByCountryAndProductTaxCode(Country $country, $productTaxCode)
    {
        $qb = $this->createQueryBuilder('taxRule');
        $qb
            ->join('taxRule.taxJurisdiction', 'taxJurisdiction')
            ->join('taxRule.productTaxCode', 'productTaxCode')
            ->leftJoin('taxJurisdiction.zipCodes', 'zipCodes')
            ->where($qb->expr()->eq('taxJurisdiction.country', ':country'))
            ->andWhere($qb->expr()->eq('productTaxCode.code', ':productTaxCode'))
            ->andWhere($qb->expr()->isNull('taxJurisdiction.region'))
            ->andWhere($qb->expr()->isNull('taxJurisdiction.regionText'))
            ->andWhere($qb->expr()->isNull('zipCodes.id'))
            ->setParameters([
                'country' => $country,
                'productTaxCode' => $productTaxCode
            ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find TaxRules by Country
     *
     * @param string      $productTaxCode
     * @param Country     $country
     * @param Region|null $region
     * @param null        $regionText
     * @return TaxRule[]
     */
    public function findByCountryAndRegionAndProductTaxCode(
        $productTaxCode,
        Country $country,
        Region $region = null,
        $regionText = null
    ) {
        $qb = $this->createQueryBuilder('taxRule');
        $qb
            ->join('taxRule.productTaxCode', 'productTaxCode')
            ->leftJoin('taxRule.taxJurisdiction', 'taxJurisdiction')
            ->leftJoin('taxJurisdiction.zipCodes', 'zipCodes')
            ->where($qb->expr()->eq('taxJurisdiction.country', ':country'))
            ->andWhere($qb->expr()->eq('productTaxCode.code', ':productTaxCode'))
            ->andWhere($qb->expr()->isNull('zipCodes.id'))
            ->setParameters([
                'country' => $country,
                'productTaxCode' => $productTaxCode
            ]);

        if ($region) {
            $qb->andWhere($qb->expr()->eq('taxJurisdiction.region', ':region'))
                ->setParameter('region', $region);

        } elseif ($regionText) {
            $qb->andWhere($qb->expr()->eq('taxJurisdiction.regionText', ':region_text'))
                ->setParameter('region_text', $regionText);
        } else {
            throw new \InvalidArgumentException('Region or Region Text arguments missed');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find TaxRules by ZipCode (with Region/Country check)
     *
     * @param string  $productTaxCode
     * @param string  $zipCode
     * @param Country $country

     * @param Region  $region
     * @param string  $regionText
     * @return TaxRule[]
     */
    public function findByZipCodeAndProductTaxCode(
        $productTaxCode,
        $zipCode,
        Country $country,
        Region $region = null,
        $regionText = null
    ) {
        $qb = $this->createQueryBuilder('taxRule');
        $qb
            ->join('taxRule.taxJurisdiction', 'taxJurisdiction')
            ->join('taxRule.productTaxCode', 'productTaxCode')
            ->leftJoin('taxJurisdiction.zipCodes', 'zipCodes')
            ->where($qb->expr()->eq('taxJurisdiction.country', ':country'))
            ->andWhere($qb->expr()->eq('productTaxCode.code', ':productTaxCode'))
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
                'zipCodeForRange' => (int)$zipCode,
                'productTaxCode' => $productTaxCode
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
