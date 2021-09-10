<?php

namespace Oro\Bundle\PricingBundle\ORM\Walker;

use Doctrine\ORM\Query\AST;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Component\DoctrineUtils\ORM\Walker\AbstractOutputResultModifier;

/**
 * Query walker for price shard
 */
class PriceShardOutputResultModifier extends AbstractOutputResultModifier
{
    public const ORO_PRICING_SHARD_MANAGER = 'oro_pricing.shard_manager';
    public const HINT_PRICE_SHARD = 'HINT_PRICE_SHARD';

    /**
     * {@inheritdoc}
     */
    public function walkSelectStatement(AST\SelectStatement $AST, string $result)
    {
        if ($this->getQuery()->hasHint(self::ORO_PRICING_SHARD_MANAGER)) {
            return $this->parseSql($result);
        }

        return $result;
    }

    /**
     * @param string $sql
     * @return string
     * @throws \Exception
     */
    protected function parseSql($sql)
    {
        $shardManager = $this->getShardManager();
        foreach ($shardManager->getShardMap() as $entityClass => $baseTableName) {
            if (!str_contains($sql, ' ' . $baseTableName . ' ')) {
                continue;
            }
            //find aliases
            $matches = [];
            preg_match_all('~' . $baseTableName . ' ([\w\_\-]+) ~', $sql, $matches);
            $aliases = $matches[1];

            $tableMap = [];
            $discriminationColumn = $shardManager->getDiscriminationColumn($entityClass);
            foreach ($aliases as $alias) {
                $discriminationField = $alias . '.' . $discriminationColumn;
                $discriminationValue = $this->detectDiscriminationValue($sql, $discriminationField, []);
                if ($discriminationValue === null) {
                    continue;
                }
                $tableMap[$alias] = $shardManager->getEnabledShardName(
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
            //check direct id
            preg_match_all('~[ (]' . $discriminationField . ' = ([\d]+)[ )]~', $sql, $matches);
            if (empty($matches[1])) {
                $expr = '~[ (]([\d]+) = ' . $discriminationField . '[ )]~';
                preg_match_all($expr, $sql, $matches);
            }
            if (!empty($matches[1])) {
                return $matches[1][0];
            }
            preg_match_all('~[ (]' . $discriminationField . ' = ([.\w\_\-]+)[ )]~', $sql, $matches);
            if (empty($matches[1])) {
                $expr = '~[ (]([.\w\_\-]+) = ' . $discriminationField . '[ )]~';
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
        foreach ($this->parserResult->getParameterMappings() as $name => $parameterApply) {
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
