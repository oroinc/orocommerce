<?php

namespace Oro\Bundle\TaxBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Model\TaxCodes;

/**
 * The ORM repository for TaxRule entity.
 */
class TaxRuleRepository extends ServiceEntityRepository
{
    private AclHelper $aclHelper;

    public function __construct(ManagerRegistry $registry, string $entityClass, AclHelper $aclHelper)
    {
        parent::__construct($registry, $entityClass);
        $this->aclHelper = $aclHelper;
    }

    /**
     * Finds tax rules by country and tax codes.
     *
     * @return TaxRule[]
     */
    public function findByCountryAndTaxCode(TaxCodes $taxCodes, Country $country): array
    {
        $qb = $this->createRegionQueryBuilder($country, null, null);
        $qb->andWhere('zipCodes.id IS NULL');

        $this->joinTaxCodes($qb, $taxCodes);

        return $this->aclHelper->apply($qb)->getResult();
    }

    /**
     * Finds tax rules by country, region and tax codes.
     *
     * @return TaxRule[]
     */
    public function findByRegionAndTaxCode(
        TaxCodes $taxCodes,
        Country $country,
        ?Region $region,
        ?string $regionText
    ): array {
        $this->assertRegion($region, $regionText);

        $qb = $this->createRegionQueryBuilder($country, $region, $regionText);
        $qb->andWhere('zipCodes.id IS NULL');

        $this->joinTaxCodes($qb, $taxCodes);

        return $this->aclHelper->apply($qb)->getResult();
    }

    /**
     * Finds tax rules by ZIP code (with region/country check) and tax codes.
     *
     * @return TaxRule[]
     */
    public function findByZipCodeAndTaxCode(
        TaxCodes $taxCodes,
        ?string $zipCode,
        Country $country,
        ?Region $region,
        ?string $regionText
    ): array {
        $this->assertRegion($region, $regionText);

        $qb = $this->createRegionQueryBuilder($country, $region, $regionText);
        $this->applyZipCodeRestrictions($qb, $zipCode);
        $this->joinTaxCodes($qb, $taxCodes);

        return $this->aclHelper->apply($qb)->getResult();
    }

    /**
     * Finds tax rules by ZIP code (with country check) and tax codes.
     *
     * @return TaxRule[]
     */
    public function findByCountryAndZipCodeAndTaxCode(
        TaxCodes $taxCodes,
        ?string $zipCode,
        Country $country
    ): array {
        $qb = $this->createCountryQueryBuilder($country);
        $this->applyZipCodeRestrictions($qb, $zipCode);
        $this->joinTaxCodes($qb, $taxCodes);

        return $this->aclHelper->apply($qb)->getResult();
    }

    private function createCountryQueryBuilder(Country $country): QueryBuilder
    {
        $qb = $this->createQueryBuilder('taxRule');
        $qb
            ->join('taxRule.taxJurisdiction', 'taxJurisdiction')
            ->leftJoin('taxJurisdiction.zipCodes', 'zipCodes')
            ->where('taxJurisdiction.country = :country')
            ->setParameter('country', $country);

        return $qb;
    }

    private function createRegionQueryBuilder(Country $country, ?Region $region, ?string $regionText): QueryBuilder
    {
        $qb = $this->createCountryQueryBuilder($country);

        if (null !== $region) {
            $qb
                ->andWhere('taxJurisdiction.region = :region')
                ->setParameter('region', $region);
        } else {
            $qb->andWhere('taxJurisdiction.region IS NULL');
        }

        if ($regionText) {
            $qb
                ->andWhere('taxJurisdiction.regionText = :region_text')
                ->setParameter('region_text', $regionText);
        } else {
            $qb->andWhere('taxJurisdiction.regionText IS NULL');
        }

        return $qb;
    }

    private function applyZipCodeRestrictions(QueryBuilder $queryBuilder, ?string $zipCode): void
    {
        $queryBuilder
            ->andWhere(
                '(CAST(zipCodes.zipRangeStart as int) <= :zipCodeForRange'
                . ' AND CAST(zipCodes.zipRangeEnd as int) >= :zipCodeForRange)'
                . ' OR zipCodes.zipCode = :zipCode'
            )
            ->setParameter('zipCode', $zipCode)
            ->setParameter('zipCodeForRange', (int)$zipCode);
    }

    private function joinTaxCodes(QueryBuilder $queryBuilder, TaxCodes $taxCodes): void
    {
        $plainTaxCodes = $taxCodes->getPlainTypedCodes();
        foreach ($taxCodes->getAvailableTypes() as $type) {
            $alias = sprintf('%sTaxCode', $type);
            $queryBuilder->leftJoin('taxRule.' . $alias, $alias);
            if (\array_key_exists($type, $plainTaxCodes)) {
                $queryBuilder
                    ->andWhere(sprintf('%s.code IN (:%s)', $alias, $type))
                    ->setParameter($type, $plainTaxCodes[$type]);
            }
        }
    }

    private function assertRegion(?Region $region, ?string $regionText): void
    {
        if (null === $region && !$regionText) {
            throw new \InvalidArgumentException('Region or Region Text arguments missed');
        }
    }
}
