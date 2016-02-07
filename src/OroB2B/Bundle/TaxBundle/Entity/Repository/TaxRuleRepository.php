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
     * @param string  $accountTaxCode
     * @return TaxRule[]
     */
    public function findByCountryAndProductTaxCodeAndAccountTaxCode(Country $country, $productTaxCode, $accountTaxCode)
    {
        $qb = $this->createQueryBuilder('taxRule');
        $qb
            ->join('taxRule.taxJurisdiction', 'taxJurisdiction')
            ->join('taxRule.productTaxCode', 'productTaxCode')
            ->leftJoin('taxJurisdiction.zipCodes', 'zipCodes')
            ->where($qb->expr()->eq('taxJurisdiction.country', ':country'))
            ->andWhere($qb->expr()->eq('productTaxCode.code', ':productTaxCode'))
            ->andWhere($qb->expr()->eq('accountTaxCode.code', ':accountTaxCode'))
            ->andWhere($qb->expr()->isNull('taxJurisdiction.region'))
            ->andWhere($qb->expr()->isNull('taxJurisdiction.regionText'))
            ->andWhere($qb->expr()->isNull('zipCodes.id'))
            ->setParameters([
                'country' => $country,
                'productTaxCode' => $productTaxCode,
                'accountTaxCode' => $accountTaxCode
            ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find TaxRules by Country
     *
     * @param string      $productTaxCode
     * @param string      $accountTaxCode
     * @param Country     $country
     * @param Region|null $region
     * @param null        $regionText
     * @return TaxRule[]
     */
    public function findByCountryAndRegionAndProductTaxCodeAndAccountTaxCode(
        $productTaxCode,
        $accountTaxCode,
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
            ->andWhere($qb->expr()->eq('accountTaxCode.code', ':accountTaxCode'))
            ->andWhere($qb->expr()->isNull('zipCodes.id'))
            ->setParameters([
                'country' => $country,
                'productTaxCode' => $productTaxCode,
                'accountTaxCode' => $accountTaxCode
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
     * @param string  $accountTaxCode
     * @param string  $zipCode
     * @param Country $country
     * @param Region  $region
     * @param string  $regionText
     * @return TaxRule[]
     */
    public function findByZipCodeAndProductTaxCodeAndAccountTaxCode(
        $productTaxCode,
        $accountTaxCode,
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
            ->andWhere($qb->expr()->eq('accountTaxCode.code', ':accountTaxCode'))
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
                'productTaxCode' => $productTaxCode,
                'accountTaxCode' => $accountTaxCode
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
