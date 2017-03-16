<?php

namespace Oro\Bundle\PricingBundle\ORM\Walker;

use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\AST;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;

class PriceShardWalker extends SqlWalker
{
    const ORO_PRICING_SHARD_MANAGER = 'oro_pricing.shard_manager';
    const HINT_PRICE_SHARD = 'HINT_PRICE_SHARD';

    /**
     * @var \Doctrine\ORM\Query\ParserResult
     */
    protected $parsingResult;

    /**
     * {@inheritdoc}
     */
    public function __construct($query, $parserResult, array $queryComponents)
    {
        $this->parsingResult = $parserResult;
        parent::__construct($query, $parserResult, $queryComponents);
    }

    /**
     * {@inheritdoc}
     */
    public function walkSelectStatement(AST\SelectStatement $AST)
    {
        $sql = parent::walkSelectStatement($AST);
        $sql = $this->parseSql($sql);

        return $sql;
    }

    /**
     * SELECT * FROM oro_product o0_
     * LEFT JOIN oro_price_list_to_product o2_ ON (o2_.price_list_id = ? AND o2_.product_id = o0_.id)  // level 2
     * LEFT JOIN oro_price_list o3_ ON (o2_.price_list_id = o3_.id)                                    // level 1
     * LEFT JOIN oro_price_product o1_ ON (o1_.product_id = o0_.id AND o1_.price_list_id = o3_.id)     // level 0
     *
     * @param string $sql
     * @return string
     * @throws \Exception
     */
    protected function parseSql($sql)
    {
        $shardManager = $this->getShardManager();
        foreach ($shardManager->getShardMap() as $entityClass => $baseTableName) {
            if (strpos($sql, ' ' . $baseTableName . ' ') === false) {
                continue;
            }
            //find aliases
            $matches = [];
            preg_match_all('~' . $baseTableName . ' ([\w_-]+) ~', $sql, $matches);
            $aliases = $matches[1];

            $tableMap = [];
            $discriminationColumn = $shardManager->getDiscriminationColumn($entityClass);
            foreach ($aliases as $alias) {
                $discriminationField = $alias . '.' . $discriminationColumn;
                $discriminationValue = $this->detectDiscriminationValue($sql, $discriminationField, []);
                if ($discriminationValue === null) {
                    throw new \RuntimeException('Cant\'t detect shard name by query parameters');
                }
                $tableMap[$alias] = $shardManager->getShardName(
                    $entityClass,
                    ['priceList' => $discriminationValue]
                );
            }
            $sql = $this->replaceTables($sql, $tableMap, $baseTableName);
        }

        return $sql;
    }

    /**
     * @param string $sql
     * @param string $discriminationField
     * @param array $ignoreFields
     * @return int|null
     */
    protected function detectDiscriminationValue($sql, $discriminationField, array $ignoreFields)
    {
        $value = null;
        $parameterSet = $discriminationField . ' = ?';
        $foundParameterPosition = strpos($sql, $parameterSet);
        if ($foundParameterPosition !== false) {
            //get parameter value
            return $this->findParameterValue($sql, $foundParameterPosition, $parameterSet);
        } else {
            preg_match_all('~[ (]' . $discriminationField . ' = ([.\w_-]+)[ )]~', $sql, $matches);
            if (empty($matches[1])) {
                $expr = '~[ (]([.\w_-]+) = ' . $discriminationField . '[ )]~';
                preg_match_all($expr, $sql, $matches);
            }
            $ignoreFields[] = $discriminationField;
            foreach ($matches[1] as $match) {
                if (in_array($match, $ignoreFields)) {
                    //protect cycling
                    continue;
                }
                $value = $this->detectDiscriminationValue($sql, $match, $ignoreFields);
                if ($value !== null) {
                    break;
                }
            }
        }

        return $value;
    }

    /**
     * @param $sql
     * @param $foundParameterPosition
     * @param $parameterSet
     * @return mixed
     */
    protected function findParameterValue($sql, $foundParameterPosition, $parameterSet)
    {
        $parameterName = null;
        $searchLength = $foundParameterPosition + strlen($parameterSet);
        $parameterNumber = substr_count($sql, '?', 0, $searchLength) - 1;
        foreach ($this->parsingResult->getParameterMappings() as $name => $parameterApply) {
            if (in_array($parameterNumber, $parameterApply)) {
                $parameterName = $name;
                break;
            }
        }
        return $this->getQuery()->getParameter($parameterName)->getValue();
    }

    /**
     * @param string $sql
     * @param $tableMap
     * @param string $baseTableName
     * @return mixed
     */
    protected function replaceTables($sql, array $tableMap, $baseTableName)
    {
        foreach ($tableMap as $sqlAlias => $realTableName) {
            $from = ' ' . $baseTableName . ' ' . $sqlAlias;
            $to = ' ' . $realTableName . ' ' . $sqlAlias;
            $sql = str_replace($from, $to, $sql);
        }

        return $sql;
    }

    /**
     * @return ShardManager
     * @throws \RuntimeException
     */
    protected function getShardManager()
    {
        $query = $this->getQuery();
        if (!$query->hasHint(self::ORO_PRICING_SHARD_MANAGER)
            || !$query->getHint(self::ORO_PRICING_SHARD_MANAGER) instanceof ShardManager) {
            throw new \RuntimeException('Shard manager was not found');
        }

        return $query->getHint(self::ORO_PRICING_SHARD_MANAGER);
    }
}
