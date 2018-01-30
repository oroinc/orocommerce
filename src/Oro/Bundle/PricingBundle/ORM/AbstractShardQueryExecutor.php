<?php

namespace Oro\Bundle\PricingBundle\ORM;

use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\NativeQueryExecutorHelper;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;

abstract class AbstractShardQueryExecutor implements ShardQueryExecutorInterface
{
    /**
     * @var array
     */
    protected $tablesColumns;

    /**
     * @var NativeQueryExecutorHelper
     */
    protected $helper;

    /**
     * @var ShardManager
     */
    protected $shardManager;

    /**
     * @param NativeQueryExecutorHelper $helper
     * @param ShardManager $shardManager
     */
    public function __construct(NativeQueryExecutorHelper $helper, ShardManager $shardManager)
    {
        $this->shardManager = $shardManager;
        $this->helper = $helper;
    }

    /**
     * {@inheritDoc}
     */
    abstract public function execute($className, array $fields, QueryBuilder $selectQueryBuilder);

    /**
     * @param              $className
     * @param array        $fields
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

    /**
     * @param string $className
     * @param array $fields
     * @return array
     */
    protected function getColumns($className, array $fields)
    {
        $result = [];

        foreach ($fields as $field) {
            if (!isset($this->tablesColumns[$className][$field])) {
                $classMetadata = $this->helper->getClassMetadata($className);
                if (!$classMetadata->hasField($field) && !$classMetadata->hasAssociation($field)) {
                    throw new \InvalidArgumentException(sprintf('Field %s is not known for %s', $field, $className));
                }
                if ($classMetadata->hasAssociation($field)) {
                    $mapping = $classMetadata->getAssociationMapping($field);
                    $this->tablesColumns[$className][$field] = array_shift($mapping['joinColumnFieldNames']);
                } else {
                    $this->tablesColumns[$className][$field] = $classMetadata->getColumnName($field);
                }
            }
            $result[] = $this->tablesColumns[$className][$field];
        }

        return $result;
    }
}
