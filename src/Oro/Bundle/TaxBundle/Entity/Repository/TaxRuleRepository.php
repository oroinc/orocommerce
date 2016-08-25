<?php

namespace Oro\Bundle\TaxBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Model\TaxCodes;

class TaxRuleRepository extends EntityRepository
{
    /**
     * @param Country $country
     * @return QueryBuilder
     */
    protected function createCountryQueryBuilder(Country $country)
    {
        $qb = $this->createQueryBuilder('taxRule');
        $qb
            ->join('taxRule.taxJurisdiction', 'taxJurisdiction')
            ->leftJoin('taxJurisdiction.zipCodes', 'zipCodes')
            ->where($qb->expr()->eq('taxJurisdiction.country', ':country'))
            ->setParameter('country', $country);

        return $qb;
    }

    /**
     * @param Country $country
     * @param Region $region
     * @param string $regionText
     * @return QueryBuilder
     */
    protected function createRegionQueryBuilder(Country $country, Region $region = null, $regionText = null)
    {
        $qb = $this->createCountryQueryBuilder($country);

        if ($region) {
            $qb->andWhere($qb->expr()->eq('taxJurisdiction.region', ':region'))
                ->setParameter('region', $region);
        } else {
            $qb->andWhere($qb->expr()->isNull('taxJurisdiction.region'));
        }

        if ($regionText) {
            $qb->andWhere($qb->expr()->eq('taxJurisdiction.regionText', ':region_text'))
                ->setParameter('region_text', $regionText);
        } else {
            $qb->andWhere($qb->expr()->isNull('taxJurisdiction.regionText'));
        }

        return $qb;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param TaxCodes $taxCodes
     */
    protected function joinTaxCodes(QueryBuilder $queryBuilder, TaxCodes $taxCodes)
    {
        $plainTaxCodes = $taxCodes->getPlainTypedCodes();

        foreach ($taxCodes->getAvailableTypes() as $type) {
            $alias = sprintf('%sTaxCode', $type);
            $joinAlias = sprintf('%s.code', $alias);

            $queryBuilder->leftJoin(sprintf('taxRule.%s', $alias), $alias);

            if (array_key_exists($type, $plainTaxCodes)) {
                $queryBuilder
                    ->andWhere($queryBuilder->expr()->in($joinAlias, sprintf(':%s', $type)))
                    ->setParameter($type, $plainTaxCodes[$type]);
            }
        }
    }

    /**
     * Find TaxRules by Country and TaxCodes
     *
     * @param TaxCodes $taxCodes
     * @param Country $country
     * @return TaxRule[]
     */
    public function findByCountryAndTaxCode(TaxCodes $taxCodes, Country $country)
    {
        $qb = $this->createRegionQueryBuilder($country);
        $qb->andWhere($qb->expr()->isNull('zipCodes.id'));

        $this->joinTaxCodes($qb, $taxCodes);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find TaxRules by Country, Region and TaxCodes
     *
     * @param TaxCodes $taxCodes
     * @param Country $country
     * @param Region|null $region
     * @param null $regionText
     * @return TaxRule[]
     */
    public function findByRegionAndTaxCode(
        TaxCodes $taxCodes,
        Country $country,
        Region $region = null,
        $regionText = null
    ) {
        $this->assertRegion($region, $regionText);

        $qb = $this->createRegionQueryBuilder($country, $region, $regionText);
        $qb->andWhere($qb->expr()->isNull('zipCodes.id'));

        $this->joinTaxCodes($qb, $taxCodes);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find TaxRules by ZipCode (with Region/Country check) and TaxCodes
     *
     * @param TaxCodes $taxCodes
     * @param string $zipCode
     * @param Country $country
     * @param Region $region
     * @param string $regionText
     * @return TaxRule[]
     */
    public function findByZipCodeAndTaxCode(
        TaxCodes $taxCodes,
        $zipCode,
        Country $country,
        Region $region = null,
        $regionText = null
    ) {
        $this->assertRegion($region, $regionText);

        $qb = $this->createRegionQueryBuilder($country, $region, $regionText);
        $qb
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()->lte('CAST(zipCodes.zipRangeStart as int)', ':zipCodeForRange'),
                        $qb->expr()->gte('CAST(zipCodes.zipRangeEnd as int)', ':zipCodeForRange')
                    ),
                    $qb->expr()->eq('zipCodes.zipCode', ':zipCode')
                )
            )
            ->setParameter('zipCode', $zipCode)
            ->setParameter('zipCodeForRange', (int)$zipCode);

        $this->joinTaxCodes($qb, $taxCodes);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Region|null $region
     * @param string|null $regionText
     */
    protected function assertRegion(Region $region = null, $regionText = null)
    {
        if (!$region && !$regionText) {
            throw new \InvalidArgumentException('Region or Region Text arguments missed');
        }
    }
}
