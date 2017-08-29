<?php

namespace Oro\Bundle\PromotionBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Psr\Log\LoggerInterface;

class MigratePromotionDataQuery extends ParametrizedSqlMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    private function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        if ($this->connection->getDatabasePlatform() instanceof MySqlPlatform) {
            $this->moveDataFromAppliedDiscountToAppliedPromotionMySQL($logger);
        } else {
            $this->moveDataFromAppliedDiscountToAppliedPromotionPostgreSQL($logger);
        }
        $this->migrateSourcePromotionId($logger, $dryRun);
        $this->migratePromotionData($logger, $dryRun);

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->connection->executeUpdate(
            'UPDATE oro_promotion_applied_discount SET updated_at = :updatedAt',
            ['updatedAt' => $now],
            ['updatedAt' => Type::DATETIME]
        );
        $this->connection->executeUpdate(
            'UPDATE oro_promotion_applied SET updated_at = :updatedAt, created_at = :createdAt',
            [
                'updatedAt' => $now,
                'createdAt' => $now
            ],
            [
                'updatedAt' => Type::DATETIME,
                'createdAt' => Type::DATETIME
            ]
        );
    }

    /**
     * @param LoggerInterface $logger
     */
    private function moveDataFromAppliedDiscountToAppliedPromotionMySQL(LoggerInterface $logger)
    {
        $insert = 'INSERT 
          INTO oro_promotion_applied(
            order_id,
            promotion_id,
            source_promotion_id,
            promotion_name, type,
            config_options,
            created_at,
            updated_at
          )
          SELECT 
            ad.order_id,
            ad.promotion_id,
            ad.promotion_id,
            ad.promotion_name,
            ad.type,
            to_json(CAST(ad.config_options AS TEXT)),
            ad.created_at,
            ad.updated_at
          FROM oro_promotion_applied_discount ad
          GROUP BY 
            ad.order_id,
            ad.promotion_id,
            ad.promotion_name,
            ad.type,
            CAST(ad.config_options AS TEXT),
            ad.created_at,
            ad.updated_at
        ';
        $this->logQuery($logger, $insert);
        $this->connection->executeQuery($insert);

        $update = 'UPDATE 
          oro_promotion_applied AS ap,
          oro_promotion_applied_discount AS ad
        SET ad.applied_promotion_id = ap.id
        WHERE
          ad.order_id = ap.order_id
          AND ad.promotion_id = ap.promotion_id
          AND ad.promotion_name = ap.promotion_name
          AND ad.type = ap.type';

        $this->logQuery($logger, $update);
        $this->connection->executeQuery($update);
    }

    /**
     * @param LoggerInterface $logger
     */
    private function moveDataFromAppliedDiscountToAppliedPromotionPostgreSQL(LoggerInterface $logger)
    {
        $insert = 'INSERT 
          INTO oro_promotion_applied(
            order_id,
            promotion_id,
            source_promotion_id,
            promotion_name, type,
            config_options,
            created_at,
            updated_at
          )
          SELECT 
            ad.order_id,
            ad.promotion_id,
            ad.promotion_id,
            ad.promotion_name,
            ad.type,
            to_json(CAST(ad.config_options as TEXT)),
            ad.created_at,
            ad.updated_at
          FROM oro_promotion_applied_discount ad
          GROUP BY 
            ad.order_id,
            ad.promotion_id,
            ad.promotion_name,
            ad.type,
            CAST(ad.config_options as TEXT),
            ad.created_at,
            ad.updated_at
        ';
        $this->logQuery($logger, $insert);
        $this->connection->executeQuery($insert);

        $updateForExistingPromotions = 'UPDATE
          oro_promotion_applied_discount
        SET (applied_promotion_id) =
        (SELECT id
        FROM oro_promotion_applied
        WHERE
          order_id = oro_promotion_applied_discount.order_id
          AND promotion_id = oro_promotion_applied_discount.promotion_id)';
        $this->logQuery($logger, $updateForExistingPromotions);
        $this->connection->executeQuery($updateForExistingPromotions);

        $updateForRemovedPromotions = 'UPDATE
          oro_promotion_applied_discount
        SET (applied_promotion_id) =
        (SELECT id
         FROM oro_promotion_applied
         WHERE
           order_id = oro_promotion_applied_discount.order_id
           AND promotion_name = oro_promotion_applied_discount.promotion_name
            AND type = oro_promotion_applied_discount.type
          AND promotion_id IS NULL)
        WHERE promotion_id IS NULL';
        $this->logQuery($logger, $updateForRemovedPromotions);
        $this->connection->executeQuery($updateForRemovedPromotions);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     * @throws \Doctrine\DBAL\DBALException
     */
    private function migratePromotionData(LoggerInterface $logger, $dryRun = false)
    {
        $minSortOrder = $this->getMinimumSortOrder($logger);

        $ordersWithDiscountsSelect = 'SELECT 
          o.id,
          o.customer_id,
          o.customer_user_id
          FROM oro_order o
          WHERE EXISTS(SELECT 1 FROM oro_promotion_applied ap WHERE ap.order_id = o.id)';
        $this->logQuery($logger, $ordersWithDiscountsSelect);
        $stmt = $this->connection->executeQuery($ordersWithDiscountsSelect);

        while ($orderRow = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $discountsSelect = 'SELECT
                ap.id,
                ap.promotion_name,
                ap.source_promotion_id,
                ad.line_item_id
            FROM oro_promotion_applied ap
            INNER JOIN oro_promotion_applied_discount ad ON ad.applied_promotion_id = ap.id
            WHERE ap.order_id = :orderId
            ORDER BY ap.id DESC';
            $params = ['orderId' => $orderRow['id']];
            $types = ['orderId' => Type::INTEGER];
            $this->logQuery($logger, $discountsSelect, $params, $types);

            $discountStmt = $this->connection->executeQuery($discountsSelect, $params, $types);

            $sortOrder = $minSortOrder;
            $expression = $this->getCustomerRestrictionExpression($orderRow);
            $promotionsInfo = [];
            $lastKey = null;
            while ($discountRow = $discountStmt->fetch(\PDO::FETCH_ASSOC)) {
                $key = $discountRow['promotion_name'] . ':' . (int)$discountRow['source_promotion_id'];
                $lastKey = $key;
                if (!array_key_exists($key, $promotionsInfo)) {
                    $sortOrder -= 10;

                    $promotionsInfo[$key]['sort_order'] = $sortOrder;
                    $promotionsInfo[$key]['name'] = $discountRow['promotion_name'];
                    $promotionsInfo[$key]['id'] = $discountRow['source_promotion_id'];
                    $promotionsInfo[$key]['line_items'] = [];
                }

                $promotionsInfo[$key]['applied_to'][] = $discountRow['id'];
                if (!empty($discountRow['line_item_id'])) {
                    $promotionsInfo[$key]['line_items'][] = $discountRow['line_item_id'];
                }
            }
            $promotionsInfo[$lastKey]['last'] = true;

            foreach ($promotionsInfo as $info) {
                if (empty($info['line_items'])) {
                    $segmentDefinition = $this->getSegmentDefinitionForOrder($logger, $orderRow['id']);
                } else {
                    $segmentDefinition = $this->getSegmentDefinitionForLineItems($logger, $info['line_items']);
                }

                $promotionData = [
                    'id' => $info['id'],
                    'useCoupons' => false,
                    'rule' => [
                        'name' => $info['name'],
                        'expression' => $expression,
                        'sortOrder' => $info['sort_order'],
                        'isStopProcessing' => !empty($info['last'])
                    ],
                    'scopes' => [],
                    'productsSegment' => [
                        'definition' => json_encode($segmentDefinition)
                    ]
                ];

                $updateQuery = 'UPDATE oro_promotion_applied 
                  SET promotion_data = :promotionData 
                  WHERE id IN(' . implode(',', $info['applied_to']) . ')';
                $params = ['promotionData' => $promotionData];
                $types = ['promotionData' => Type::JSON_ARRAY];
                $this->logQuery($logger, $updateQuery, $params, $types);

                if (!$dryRun) {
                    $this->connection->executeUpdate($updateQuery, $params, $types);
                }
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     * @param int $orderId
     * @return array
     */
    private function getSegmentDefinitionForOrder(LoggerInterface $logger, $orderId): array
    {
        if ($this->connection->getDatabasePlatform() instanceof MySqlPlatform) {
            $selectQuery = 'SELECT
                GROUP_CONCAT(li.product_id) as product_ids
              FROM oro_order_line_item li
              WHERE 
                li.order_id = :orderId
              GROUP BY li.order_id';
        } else {
            $selectQuery = "SELECT
                array_to_string(array_agg(li.product_id), ',') as product_ids
              FROM oro_order_line_item li
              WHERE 
                li.order_id = :orderId
              GROUP BY li.order_id";
        }

        $params = ['orderId' => $orderId];
        $types = ['orderId' => Type::INTEGER];

        $this->logQuery($logger, $selectQuery, $params, $types);
        $productIds = $this->connection->fetchColumn($selectQuery, $params, 0, $types);

        return $this->getProductsInSegmentDefinition($productIds);
    }

    /**
     * @param array $lineItems
     * @return array
     */
    private function getSegmentDefinitionForLineItems(LoggerInterface $logger, array $lineItems): array
    {
        if ($this->connection->getDatabasePlatform() instanceof MySqlPlatform) {
            $selectQuery = 'SELECT
                GROUP_CONCAT(li.product_id) as product_ids
              FROM oro_order_line_item li
              WHERE 
                li.id IN(' . implode(',', $lineItems) . ')
              GROUP BY li.order_id';
        } else {
            $selectQuery = "SELECT
                array_to_string(array_agg(li.product_id), ',') as product_ids
              FROM oro_order_line_item li
              WHERE 
                li.id IN(" . implode(',', $lineItems) . ")
              GROUP BY li.order_id";
        }

        $this->logQuery($logger, $selectQuery);
        $productIds = $this->connection->fetchColumn($selectQuery);

        return $this->getProductsInSegmentDefinition($productIds);
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

    /**
     * @param LoggerInterface $logger
     * @return int
     */
    private function getMinimumSortOrder(LoggerInterface $logger): int
    {
        $selectMinimumPromotionOrder = 'SELECT 
          MIN(r.sort_order) 
          FROM oro_promotion p 
          INNER JOIN oro_rule r ON p.rule_id = r.id';
        $this->logQuery($logger, $selectMinimumPromotionOrder);

        return (int)$this->connection->fetchColumn($selectMinimumPromotionOrder);
    }

    /**
     * @param array $orderRow
     * @return string
     */
    private function getCustomerRestrictionExpression(array $orderRow): string
    {
        $expression = 'customer.id = ' . $orderRow['customer_id'];
        if (!empty($orderRow['customer_user_id'])) {
            $expression .= ' AND customerUser.id = ' . $orderRow['customer_user_id'];
        }

        return $expression;
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    private function migrateSourcePromotionId(LoggerInterface $logger, $dryRun)
    {
        $updateExisting = 'UPDATE oro_promotion_applied 
          SET source_promotion_id = promotion_id 
          WHERE promotion_id IS NOT NULL';
        $updateRemoved = 'UPDATE oro_promotion_applied SET source_promotion_id = -id WHERE promotion_id IS NULL';
        $this->logQuery($logger, $updateExisting);
        $this->logQuery($logger, $updateRemoved);
        if (!$dryRun) {
            $this->connection->executeUpdate($updateExisting);
            $this->connection->executeUpdate($updateRemoved);
        }
    }
}
