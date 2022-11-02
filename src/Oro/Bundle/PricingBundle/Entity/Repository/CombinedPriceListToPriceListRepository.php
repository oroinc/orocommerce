<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\DBAL\Platforms\PostgreSQL94Platform;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIteratorInterface;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * CombinedPriceListToPriceList ORM Entity repository.
 */
class CombinedPriceListToPriceListRepository extends EntityRepository
{
    /**
     * @param CombinedPriceList $combinedPriceList
     * @param array|Product[] $products
     * @return CombinedPriceListToPriceList[]
     */
    public function getPriceListRelations(CombinedPriceList $combinedPriceList, array $products = [])
    {
        $qb = $this->createQueryBuilder('combinedPriceListToPriceList');

        if ($products) {
            $qb
                ->innerJoin(
                    PriceListToProduct::class,
                    'priceListToProduct',
                    Join::WITH,
                    $qb->expr()->andX(
                        $qb->expr()->eq('priceListToProduct.priceList', 'combinedPriceListToPriceList.priceList'),
                        $qb->expr()->in('priceListToProduct.product', ':products')
                    )
                )
                ->setParameter('products', $products);
        }

        $qb->orderBy('combinedPriceListToPriceList.sortOrder')
            ->where($qb->expr()->eq('combinedPriceListToPriceList.combinedPriceList', ':combinedPriceList'))
            ->setParameter('combinedPriceList', $combinedPriceList);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param PriceList[]|int[] $priceLists
     * @return BufferedQueryResultIteratorInterface
     */
    public function getCombinedPriceListsByActualPriceLists(array $priceLists)
    {
        $subQb = $this->getEntityManager()->createQueryBuilder();
        $subQb->select('1')
            ->from($this->getEntityName(), 'cpl2pl')
            ->innerJoin('cpl2pl.priceList', 'pl')
            ->where(
                $subQb->expr()->eq('pl.actual', ':isActual'),
                $subQb->expr()->eq('cpl2pl.combinedPriceList', 'cpl')
            );

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('DISTINCT cpl')
            ->from(CombinedPriceList::class, 'cpl')
            ->innerJoin(
                $this->getEntityName(),
                'priceListRelations',
                Join::WITH,
                $qb->expr()->eq('cpl', 'priceListRelations.combinedPriceList')
            )
            ->where(
                $qb->expr()->in('priceListRelations.priceList', ':priceLists'),
                $qb->expr()->not(
                    $qb->expr()->exists(
                        $subQb->getDQL()
                    )
                )
            )
            ->setParameter('priceLists', $priceLists)
            ->setParameter('isActual', false);

        return new BufferedIdentityQueryResultIterator($qb->getQuery());
    }

    /**
     * @param CombinedPriceList[]|int[] $cpls
     * @return array
     */
    public function getPriceListIdsByCpls(array $cpls)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('DISTINCT IDENTITY(cpl2pl.priceList) as priceListId')
            ->from($this->getEntityName(), 'cpl2pl')
            ->where(
                $qb->expr()->in('cpl2pl.combinedPriceList', ':cpls')
            )
            ->setParameter('cpls', $cpls);

        return array_column($qb->getQuery()->getArrayResult(), 'priceListId');
    }

    public function findFallbackCplUsingMergeFlag(CombinedPriceList $combinedPriceList): ?CombinedPriceList
    {
        $connection = $this->getEntityManager()->getConnection();
        if (!$connection->getDatabasePlatform() instanceof PostgreSQL94Platform) {
            return null;
        }

        // Search one or null CPL that contains maximum number of included price lists used in the given CPL.
        // Take into account merge flag and sort order to not break merge logic.
        // Use CPLs with more than 1 PL in the chain
        // Skip CPLs that are mentioned in oro_price_list_combined_build_activity (build in progress)
        $searchQuery = <<<SQL
        WITH aggregated_cpl AS (
            SELECT
                string_agg(price_list_id::text || (CASE WHEN merge_allowed = true THEN 't' ELSE 'f' END),
                    '_' ORDER BY sort_order) as price_lists,
                count(id) as items_count,
                combined_price_list_id
            FROM oro_cmb_pl_to_pl
            GROUP BY combined_price_list_id
            HAVING count(id) > 1
        )
        SELECT
            fallback_cpl.combined_price_list_id
        FROM aggregated_cpl under_search_cpl
        INNER JOIN aggregated_cpl fallback_cpl
            ON fallback_cpl.combined_price_list_id <> under_search_cpl.combined_price_list_id
        INNER JOIN oro_price_list_combined cpl
            ON cpl.id = fallback_cpl.combined_price_list_id
        WHERE
            under_search_cpl.combined_price_list_id = :combinedPriceListId
            AND cpl.is_prices_calculated = true
            AND NOT(EXISTS(
                SELECT 1 FROM oro_price_list_combined_build_activity where combined_price_list_id = cpl.id
            ))
            AND under_search_cpl.price_lists LIKE fallback_cpl.price_lists || '%'
        ORDER BY fallback_cpl.items_count DESC
        LIMIT 1
SQL;

        $fallbackCplId = $connection
            ->executeQuery($searchQuery, ['combinedPriceListId' => $combinedPriceList->getId()])
            ->fetchOne();

        if ($fallbackCplId) {
            return $this->getEntityManager()->find(CombinedPriceList::class, $fallbackCplId);
        }

        return null;
    }

    public function findFallbackCpl(CombinedPriceList $combinedPriceList): ?CombinedPriceList
    {
        $connection = $this->getEntityManager()->getConnection();
        if (!$connection->getDatabasePlatform() instanceof PostgreSQL94Platform) {
            return null;
        }

        // Search one or null CPL that contains maximum number of included price lists used in the given CPL.
        // Use CPLs with more than 1 PL in the chain
        // Skip CPLs that are mentioned in oro_price_list_combined_build_activity (build in progress)
        $searchQuery = <<<SQL
        WITH aggregated_cpl AS (
            SELECT
                array_agg(price_list_id) as price_lists,
                count(id)                as items_count,
                combined_price_list_id
            FROM oro_cmb_pl_to_pl
            GROUP BY combined_price_list_id
            HAVING count(id) > 1
        )
        SELECT
            fallback_cpl.combined_price_list_id
        FROM aggregated_cpl under_search_cpl
        INNER JOIN aggregated_cpl fallback_cpl
            ON fallback_cpl.combined_price_list_id <> under_search_cpl.combined_price_list_id
        INNER JOIN oro_price_list_combined cpl
            ON cpl.id = fallback_cpl.combined_price_list_id
        WHERE
            under_search_cpl.combined_price_list_id = :combinedPriceListId
            AND cpl.is_prices_calculated = true
            AND NOT(EXISTS(
                SELECT 1 FROM oro_price_list_combined_build_activity where combined_price_list_id = cpl.id
            ))
            AND under_search_cpl.price_lists @> fallback_cpl.price_lists
        ORDER BY fallback_cpl.items_count DESC
        LIMIT 1
SQL;

        $fallbackCplId = $connection
            ->executeQuery($searchQuery, ['combinedPriceListId' => $combinedPriceList->getId()])
            ->fetchOne();

        if ($fallbackCplId) {
            return $this->getEntityManager()->find(CombinedPriceList::class, $fallbackCplId);
        }

        return null;
    }


    /**
     * @param PriceList $priceList
     *
     * @return bool
     */
    public function hasCombinedPriceListWithPriceList(PriceList $priceList): bool
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('1')
            ->from($this->getEntityName(), 'cpl2pl')
            ->andWhere('cpl2pl.priceList = :priceList')
            ->setMaxResults(1)
            ->setParameters(['priceList' => $priceList,]);

        return (bool) $qb->getQuery()->getScalarResult();
    }
}
