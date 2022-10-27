<?php

namespace Oro\Bundle\PromotionBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Psr\Log\LoggerInterface;

class MigratePromotionDataQuery extends ParametrizedSqlMigrationQuery
{
    const DB_MIN_INT = -2147483648;
    const ORDERS_LIMIT = 10000;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var bool
     */
    private $dryRun;

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->execute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger, $dryRun = false)
    {
        $this->logger = $logger;
        $this->dryRun = $dryRun;

        $this->doExecute();

        parent::execute($logger);
    }

    private function doExecute()
    {
        $this->moveDataFromAppliedDiscountToAppliedPromotion();

        $this->migratePromotionData();
        $this->updateDateTimes();
    }

    private function updateDateTimes()
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->executeQuery(
            'UPDATE oro_promotion_applied_discount SET updated_at = :updatedAt',
            ['updatedAt' => $now],
            ['updatedAt' => Types::DATETIME_MUTABLE]
        );
        $this->executeQuery(
            'UPDATE oro_promotion_applied SET updated_at = :updatedAt, created_at = :createdAt',
            [
                'updatedAt' => $now,
                'createdAt' => $now
            ],
            [
                'updatedAt' => Types::DATETIME_MUTABLE,
                'createdAt' => Types::DATETIME_MUTABLE
            ]
        );
    }

    private function moveDataFromAppliedDiscountToAppliedPromotion()
    {
        // Convert not removed promotions
        $insert = 'INSERT 
          INTO oro_promotion_applied(
            id,
            order_id,
            source_promotion_id,
            promotion_name, 
            type,
            config_options,
            created_at,
            updated_at
          )
          SELECT 
            MIN(ad.id),
            ad.order_id,
            ad.promotion_id,
            ad.promotion_name,
            ad.type,' .
            $this->jsonParameterToText('ad.config_options', true) . ',
            MAX(ad.created_at),
            MAX(ad.updated_at)
          FROM oro_promotion_applied_discount ad
          WHERE promotion_id IS NOT NULL
          GROUP BY 
            ad.order_id,
            ad.promotion_id,
            ad.promotion_name,
            ad.type,' . $this->jsonParameterToText('ad.config_options');

        $this->executeQuery($insert);

        $updateNotRemovedPromotions = '
        UPDATE oro_promotion_applied_discount
        SET applied_promotion_id =
        (
          SELECT id
          FROM oro_promotion_applied
          WHERE
            order_id = oro_promotion_applied_discount.order_id
            AND source_promotion_id = oro_promotion_applied_discount.promotion_id
        ) WHERE promotion_id IS NOT NULL';

        $this->executeQuery($updateNotRemovedPromotions);

        $this->migrateDeletedOrderPromotions();
        $this->migrateDeletedLineItemPromotions();
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function migratePromotionData()
    {
        $steps = $this->getOrdersSteps();

        for ($step = 0; $step < $steps; ++$step) {
            $qb = $this->getOrdersWithPromotionsQb()
                ->select('o.id', 'o.customer_id', 'o.customer_user_id')
                ->setMaxResults(self::ORDERS_LIMIT)
                ->setFirstResult($step * self::ORDERS_LIMIT);

            $this->logQuery($this->logger, $qb->getSQL());
            $statement = $qb->execute();

            while ($orderRow = $statement->fetch(\PDO::FETCH_ASSOC)) {
                $this->migrateDiscountsForOrder($orderRow);
            }
        }
    }

    private function migrateDiscountsForOrder(array $orderRow)
    {
        $params = ['orderId' => $orderRow['id']];
        $types = ['orderId' => Types::INTEGER];

        $this->logQuery($this->logger, $this->getDiscountsSelectStatement(), $params, $types);
        $discountStmt = $this->connection->executeQuery($this->getDiscountsSelectStatement(), $params, $types);

        $expression = $this->getCustomerRestrictionExpression($orderRow);
        $promotionRows = [];

        while ($promotionRow = $discountStmt->fetch(\PDO::FETCH_ASSOC)) {
            // We need to guarantee that for migrated applied promotions sort order is always less than for any
            // existing promotion. Also applying order between migrated discounts should be persisted.
            $sortOrder = 2 * self::DB_MIN_INT + $promotionRow['id'];

            if (empty($promotionRow['line_items'])) {
                $segmentDefinition = $this->getSegmentDefinitionForOrder($orderRow['id']);
            } else {
                $segmentDefinition = $this->getSegmentDefinitionForLineItems($promotionRow['line_items']);
            }

            $promotionRow['promotion_data'] = [
                'id' => (int)$promotionRow['source_promotion_id'],
                'useCoupons' => false,
                'rule' => [
                    'name' => $promotionRow['promotion_name'],
                    'expression' => $expression,
                    'sortOrder' => $sortOrder,
                    'isStopProcessing' => false
                ],
                'scopes' => [],
                'productsSegment' => [
                    'definition' => json_encode($segmentDefinition)
                ]
            ];

            $promotionRows[] = $promotionRow;
        }

        if (!empty($promotionRows)) {
            $total = count($promotionRows);
            foreach ($promotionRows as $key => $row) {
                if ($key + 1 === $total) {
                    $row['promotion_data']['rule']['isStopProcessing'] = true;
                }

                $updateQuery = 'UPDATE oro_promotion_applied SET promotion_data = :promotionData WHERE id = :id';
                $params = ['id' => $row['id'], 'promotionData' => $row['promotion_data']];
                $types = ['id' => Types::INTEGER, 'promotionData' => Types::JSON_ARRAY];

                $this->executeQuery($updateQuery, $params, $types);
            }
        }
    }

    private function getOrdersSteps(): int
    {
        $qb = $this->getOrdersWithPromotionsQb()
            ->select('COUNT(1)');

        $this->logQuery($this->logger, $qb->getSQL());

        return (int)ceil($qb->execute()->fetchColumn() / self::ORDERS_LIMIT);
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    private function getOrdersWithPromotionsQb()
    {
        return $this->connection->createQueryBuilder()
            ->from('oro_order', 'o')
            ->where('EXISTS (SELECT 1 FROM oro_promotion_applied ap WHERE ap.order_id = o.id)');
    }

    private function getDiscountsSelectStatement(): string
    {
        $concatExpression = $this->getFieldConcatExpression('ad.line_item_id', 'line_items');

        return "SELECT
                ap.id,
                ap.source_promotion_id,
                ap.promotion_name,
                $concatExpression
            FROM oro_promotion_applied ap
            INNER JOIN oro_promotion_applied_discount ad ON ad.applied_promotion_id = ap.id
            WHERE ap.order_id = :orderId
            GROUP BY 
                ap.id,
                ap.source_promotion_id,
                ap.promotion_name
            ORDER BY ap.id";
    }

    /**
     * @param int $orderId
     * @return array
     */
    private function getSegmentDefinitionForOrder($orderId): array
    {
        $concatExpression = $this->getFieldConcatExpression('li.product_id', 'product_ids');
        $selectQuery = <<<SQL
SELECT $concatExpression
FROM oro_order_line_item li
WHERE li.order_id = :orderId
GROUP BY li.order_id
SQL;

        $params = ['orderId' => $orderId];
        $types = ['orderId' => Types::INTEGER];

        $this->logQuery($this->logger, $selectQuery, $params, $types);
        $productIds = $this->connection->fetchColumn($selectQuery, $params, 0, $types);

        return $this->getProductsInSegmentDefinition($productIds);
    }

    /**
     * @param string $lineItems
     * @return array
     */
    private function getSegmentDefinitionForLineItems($lineItems): array
    {
        $concatExpression = $this->getFieldConcatExpression('t.product_id', 'product_ids');

        $selectQuery = <<<SQL
SELECT $concatExpression
FROM
(
    SELECT DISTINCT li.product_id
    FROM oro_order_line_item li
    WHERE li.id IN($lineItems)
) t
SQL;

        $this->logQuery($this->logger, $selectQuery);
        $productIds = $this->connection->fetchColumn($selectQuery);

        return $this->getProductsInSegmentDefinition($productIds);
    }

    /**
     * @param string $fieldName
     * @param string $fieldAlias
     * @return string
     */
    private function getFieldConcatExpression($fieldName, $fieldAlias): string
    {
        if ($this->connection->getDatabasePlatform() instanceof MySqlPlatform) {
            return sprintf('GROUP_CONCAT(%s) as %s', $fieldName, $fieldAlias);
        }

        return sprintf('array_to_string(array_agg(%s), \',\') as %s', $fieldName, $fieldAlias);
    }

    /**
     * @param string $productIds
     * @return array
     */
    private function getProductsInSegmentDefinition($productIds): array
    {
        $definition = [
            'filters' => [
                [
                    [
                        'columnName' => 'id',
                        'criterion' => [
                            'filter' => 'number',
                            'data' => [
                                'value' => $productIds,
                                'type' => 9
                            ]
                        ]
                    ]
                ]
            ],
            'columns' => [
                ['name' => 'id', 'label' => 'id', 'sorting' => null, 'func' => null]
            ]
        ];

        return $definition;
    }

    private function getCustomerRestrictionExpression(array $orderRow): string
    {
        $expression = 'customer.id = ' . $orderRow['customer_id'];
        if (!empty($orderRow['customer_user_id'])) {
            $expression .= ' and customerUser.id = ' . $orderRow['customer_user_id'];
        }

        return $expression;
    }

    private function migrateDeletedOrderPromotions()
    {
        // Migrate deleted order promotions
        $insert = 'INSERT 
          INTO oro_promotion_applied(
            id,
            order_id,
            source_promotion_id,
            promotion_name, 
            type,
            config_options,
            created_at,
            updated_at
          )
          SELECT 
            ad.id,
            ad.order_id,
            -ad.id,
            ad.promotion_name,
            ad.type,' .
            $this->jsonParameterToText('ad.config_options', true) . ', 
            ad.created_at,
            ad.updated_at
          FROM oro_promotion_applied_discount ad
          WHERE promotion_id IS NULL AND line_item_id IS NULL
        ';
        $this->executeQuery($insert);

        // Update deleted order promotions
        $update = 'UPDATE oro_promotion_applied_discount 
        SET applied_promotion_id =
        (
          SELECT ap.id
          FROM oro_promotion_applied ap
          WHERE ap.source_promotion_id = -oro_promotion_applied_discount.id
        ) WHERE line_item_id IS NULL AND promotion_id IS NULL';
        $this->executeQuery($update);
    }

    private function migrateDeletedLineItemPromotions()
    {
        $duplicatedPromotionsQty = $this->getLineItemDuplicatedQueriesQty();

        //Migrate all duplicated removed line item promotions
        while ($duplicatedPromotionsQty--) {
            $insert = 'INSERT 
              INTO oro_promotion_applied(
                id,
                order_id,
                source_promotion_id,
                promotion_name, 
                type,
                config_options,
                created_at,
                updated_at
              )
              SELECT 
                MIN(ad.id),
                ad.order_id,
                0, -- mark newly added line item applied promotions
                ad.promotion_name,
                ad.type,' .
                $this->jsonParameterToText('ad.config_options', true) . ',
                MAX(ad.created_at),
                MAX(ad.updated_at)
              FROM oro_promotion_applied_discount ad
              WHERE promotion_id IS NULL AND line_item_id IS NOT NULL AND applied_promotion_id IS NULL
              GROUP BY
                ad.order_id,
                ad.promotion_name,
                ad.type,' . $this->jsonParameterToText('ad.config_options');
            $this->executeQuery($insert);

            $updateRemovedLineItemPromotions = '
            UPDATE oro_promotion_applied_discount discount
            SET applied_promotion_id =
            (
              SELECT id
              FROM oro_promotion_applied ap
              WHERE ap.order_id = discount.order_id AND ap.source_promotion_id = 0 AND discount.type = ap.type 
              AND discount.promotion_name = ap.promotion_name AND '
              . $this->jsonParameterToText('discount.config_options') . ' = '
              . $this->jsonParameterToText('ap.config_options') . '
            )
            WHERE id IN (
              SELECT t.id FROM (
                  SELECT MIN(ad.id) as id FROM oro_promotion_applied ap INNER JOIN oro_promotion_applied_discount ad
                  ON ap.order_id = ad.order_id AND ad.promotion_id IS NULL AND line_item_id IS NOT NULL 
                  AND applied_promotion_id IS NULL
                  WHERE ap.source_promotion_id = 0
                  GROUP BY ad.order_id, ad.line_item_id, ad.promotion_name, ad.type, '
                . $this->jsonParameterToText('ad.config_options') . '
              ) t
            )';

            $this->executeQuery($updateRemovedLineItemPromotions);

            $updateAddedAppliedPromotions = '
            UPDATE oro_promotion_applied
            SET source_promotion_id = -id
            WHERE source_promotion_id = 0';

            $this->executeQuery($updateAddedAppliedPromotions);
        }
    }

    /**
     * @param string $fieldName
     * @param bool $select
     * @return string
     */
    private function jsonParameterToText(string $fieldName, $select = false): string
    {
        if ($this->connection->getDatabasePlatform() instanceof MySqlPlatform) {
            return $fieldName;
        }

        return sprintf('CAST(%s AS TEXT)%s', $fieldName, $select ? '::json' : '');
    }

    private function getLineItemDuplicatedQueriesQty(): int
    {
        $maxDuplicatedDeletedLineItemsPromotions = <<<SQL
SELECT 
    MAX(qty) as qty 
FROM (
    SELECT COUNT(*) as qty
    FROM oro_promotion_applied_discount ad
    WHERE promotion_id IS NULL AND line_item_id IS NOT NULL
    GROUP BY 
        ad.order_id,
        ad.promotion_name,
        ad.type,
        {$this->jsonParameterToText('ad.config_options')},
        line_item_id
) t
SQL;

        $this->logQuery($this->logger, $maxDuplicatedDeletedLineItemsPromotions);
        $stmt = $this->connection->executeQuery($maxDuplicatedDeletedLineItemsPromotions);
        $info = $stmt->fetch(\PDO::FETCH_ASSOC);

        return (int)$info['qty'];
    }

    /**
     * @param string $query
     * @param array $params
     * @param array $types
     */
    private function executeQuery($query, array $params = [], array $types = [])
    {
        $this->logQuery($this->logger, $query);

        if (!$this->dryRun) {
            $this->connection->executeQuery($query, $params, $types);
        }
    }
}
