<?php

namespace Oro\Bundle\ProductBundle\Driver;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * This service uses to work with Product Website Reindex Request Items when data keeps in the database
 */
class ProductWebsiteReindexRequestDbalDriver implements ProductWebsiteReindexRequestDriverInterface
{
    protected const TABLE_NAME = 'oro_prod_webs_reindex_req_item';

    private ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param int $relatedJobId
     * @param array $websiteIds
     * @param array $productIds
     * @param int $chunkSize
     * @return int
     * @throws Exception
     */
    public function insertMultipleRequests(
        int $relatedJobId,
        array $websiteIds,
        array $productIds,
        int $chunkSize
    ): int {
        $count = 0;
        $insertedRowsCount = 0;
        $params = [];

        foreach ($websiteIds as $websiteId) {
            foreach ($productIds as $productId) {
                $count++;
                $params[] = $relatedJobId;
                $params[] = $websiteId;
                $params[] = $productId;

                if (($count % $chunkSize) === 0) {
                    $insertedRowsCount += $this->executeInserts($params);
                    $params = [];
                }
            }
        }

        if ($params) {
            $insertedRowsCount += $this->executeInserts($params);
        }

        return $insertedRowsCount;
    }

    /**
     * @param int $relatedJobId
     * @param int $websiteId
     * @param array $productIds
     * @return int
     * @throws Exception
     */
    public function deleteProcessedRequestItems(int $relatedJobId, int $websiteId, array $productIds): int
    {
        if (empty($productIds)) {
            return 0;
        }

        $connection = $this->getConnection();
        $qb = $connection->createQueryBuilder();
        $qb->delete(self::TABLE_NAME, 'req_item');
        $qb->where($qb->expr()->and(
            $qb->expr()->eq('req_item.related_job_id', ':relatedJobId'),
            $qb->expr()->eq('req_item.website_id', ':websiteId'),
        ));
        $qb->setParameter('relatedJobId', $relatedJobId, Types::INTEGER);
        $qb->setParameter('websiteId', $websiteId, Types::INTEGER);
        $this->applyOptimizeInByProductIds($qb, $productIds);

        return (int)$qb->execute();
    }

    /**
     * @param int $relatedJobId
     * @return array|int[]
     * @throws Exception
     */
    public function getWebsiteIdsByRelatedJobId(int $relatedJobId): array
    {
        $connection = $this->getConnection();
        $qb = $connection->createQueryBuilder();
        $qb
            ->select('req_item.website_id')
            ->from(self::TABLE_NAME, 'req_item')
            ->where($qb->expr()->eq('req_item.related_job_id', ':relatedJobId'))
            ->groupBy('req_item.website_id');

        $websiteIds = $connection->fetchFirstColumn(
            $qb->getSQL(),
            ['relatedJobId' => $relatedJobId],
            ['relatedJobId' => Types::INTEGER]
        );
        sort($websiteIds);

        return $websiteIds;
    }

    /**
     * @param int $relatedJobId
     * @param int $websiteId
     * @param int $batchSize
     * @return \Traversable
     * @throws Exception
     */
    public function getProductIdIteratorByRelatedJobIdAndWebsiteId(
        int $relatedJobId,
        int $websiteId,
        int $batchSize
    ): \Traversable {
        $lastProductId = 0;
        $connection = $this->getConnection();
        $qb = $connection->createQueryBuilder();
        $qb
            ->select('req_item.product_id')
            ->from(self::TABLE_NAME, 'req_item')
            ->where(
                $qb->expr()->eq('req_item.related_job_id', ':relatedJobId'),
                $qb->expr()->eq('req_item.website_id', ':websiteId'),
                $qb->expr()->gt('req_item.product_id', ':lastProductId')
            )
            ->orderBy('req_item.product_id')
            ->setMaxResults($batchSize)
            ->setParameter('relatedJobId', $relatedJobId, Types::INTEGER)
            ->setParameter('websiteId', $websiteId, Types::INTEGER)
            ->setParameter('lastProductId', $lastProductId, Types::INTEGER);

        $fetchNextBatch = true;
        $stm = $qb->execute();
        while ($fetchNextBatch) {
            $productIds = $stm->fetchFirstColumn();
            if ($batchSize === count($productIds)) {
                $lastProductId = end($productIds);
                $qb->setParameter('lastProductId', $lastProductId, Types::INTEGER);
                $stm = $qb->execute();
            } else {
                $fetchNextBatch = false;
            }

            if ($productIds) {
                yield $productIds;
            }
        }
    }

    /**
     * @param array $params
     * @return int
     * @throws Exception
     */
    protected function executeInserts(array $params): int
    {
        $connection = $this->getConnection();

        $rowTemplate = '(?,?,?)'; // related_job_id, website_id, product_id
        $rows = array_fill(0, count($params) / 3, $rowTemplate);

        $sqlStatement = sprintf(
            'INSERT INTO %s (related_job_id, website_id, product_id) VALUES %s
            ON CONFLICT (product_id, related_job_id, website_id) DO NOTHING',
            self::TABLE_NAME,
            implode(',', $rows)
        );

        return (int)$connection->executeStatement($sqlStatement, $params);
    }

    protected function applyOptimizeInByProductIds(QueryBuilder $qb, array $productIds): void
    {
        $orExpressions = [];
        $optimizeResult = QueryBuilderUtil::optimizeIntegerValues($productIds);
        if (!empty($optimizeResult[QueryBuilderUtil::IN])) {
            $orExpressions[] = $qb->expr()->in('req_item.product_id', ':productIdsIn');
            $qb->setParameter(
                'productIdsIn',
                $optimizeResult[QueryBuilderUtil::IN],
                Connection::PARAM_INT_ARRAY
            );
        }

        $inBetweenBatches = $optimizeResult[QueryBuilderUtil::IN_BETWEEN] ?? [];
        foreach ($inBetweenBatches as $index => $inBetweenBatch) {
            [$min, $max] = $inBetweenBatch;
            $minProductInBetweenParam = sprintf('productIdInBetweenMin_%d', $index);
            $maxProductInBetweenParam = sprintf('productIdInBetweenMax_%d', $index);
            $orExpressions[] = sprintf(
                'req_item.product_id BETWEEN :%s AND :%s',
                $minProductInBetweenParam,
                $maxProductInBetweenParam
            );
            $qb->setParameter($minProductInBetweenParam, $min, Types::INTEGER);
            $qb->setParameter($maxProductInBetweenParam, $max, Types::INTEGER);
        }

        $qb->andWhere(
            $qb->expr()->or(...$orExpressions)
        );
    }

    protected function getConnection(): Connection
    {
        return $this->registry->getConnection();
    }
}
