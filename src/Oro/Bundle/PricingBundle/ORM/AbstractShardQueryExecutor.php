<?php

namespace Oro\Bundle\PricingBundle\ORM;

use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\DatabasePlatformInterface;
use Oro\Bundle\EntityBundle\ORM\NativeQueryExecutorHelper;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;

/**
 * Abstract implementation of ShardQueryExecutorInterface
 */
abstract class AbstractShardQueryExecutor implements ShardQueryExecutorInterface
{
    /**
     * @var NativeQueryExecutorHelper
     */
    protected $helper;

    /**
     * @var ShardManager
     */
    protected $shardManager;

    /**
     * Unique field list by class. Used to construct ON DUPLICATE KEY UPDATE to prevent locking
     *
     * @var array
     */
    protected $uniqueFields = [];

    /**
     * @var int[]|array
     */
    protected $version = [];

    public function __construct(NativeQueryExecutorHelper $helper, ShardManager $shardManager)
    {
        $this->shardManager = $shardManager;
        $this->helper = $helper;
    }

    public function registerUniqueFields(string $className, array $fields)
    {
        $this->uniqueFields[$className] = $fields;
    }

    /**
     * {@inheritDoc}
     */
    abstract public function execute($className, array $fields, QueryBuilder $selectQueryBuilder);

    /**
     * @param string $className
     * @param string $sql
     * @return string
     */
    protected function applyOnDuplicateKeyUpdate(string $className, string $sql)
    {
        if (empty($this->uniqueFields[$className])) {
            return $sql;
        }

        $uniqueFields = $this->uniqueFields[$className]['search_fields'];
        $updateField = $this->uniqueFields[$className]['update_field'];
        $driver = $this->shardManager->getEntityManager()->getConnection()->getDatabasePlatform()->getName();
        switch ($driver) {
            case DatabasePlatformInterface::DATABASE_MYSQL:
                $sql .= sprintf(' ON DUPLICATE KEY UPDATE %1$s=VALUES(%1$s)', $updateField);
                break;
            case DatabasePlatformInterface::DATABASE_POSTGRESQL:
                $sql .= sprintf(
                    ' ON CONFLICT (' . implode(',', $uniqueFields) . ') DO UPDATE SET %1$s=EXCLUDED.%1$s',
                    $updateField
                );
                break;
            default:
                return $sql;
        }

        return $sql;
    }

    /**
     * @param $className
     * @param array $fields
     * @param QueryBuilder $selectQueryBuilder
     *
     * @return string
     */
    protected function getTableName($className, array $fields, QueryBuilder $selectQueryBuilder)
    {
        if (!$this->shardManager->isEntitySharded($className)) {
            return $this->helper->getTableName($className);
        }
        $index = array_search($this->shardManager->getDiscriminationField($className), $fields, true);

        $selectPart = $selectQueryBuilder->getDQLPart('select');
        /** @var Select $selectPart */
        $selectPart = $selectPart[0];
        $parts = $selectPart->getParts();
        $priceListStatement = $parts[$index];
        $position = strpos(trim($priceListStatement), ' ');
        if ($position !== false) {
            $priceListStatement = substr($priceListStatement, 0, $position + 1);
        }
        $priceListId = null;
        if (strpos(':', $priceListStatement) === 0) {
            $parameterName = substr($priceListStatement, 1);
            $priceListParameter = $selectQueryBuilder->getParameter($parameterName);
            $value = $priceListParameter->getValue();
            if ($value instanceof BasePriceList) {
                $priceListId = $value->getId();
            } else {
                $priceListId = $priceListParameter;
            }
        } elseif ($priceListStatement) {
            $priceListId = $priceListStatement;
        }

        return $this->shardManager->getEnabledShardName($className, ['priceList' => (int)$priceListId]);
    }
}
