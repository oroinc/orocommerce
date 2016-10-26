<?php

namespace Oro\Bundle\ScopeBundle\Model;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

class ScopeCriteria implements \IteratorAggregate
{
    const IS_NOT_NULL = 'IS_NOT_NULL';

    /**
     * @var array
     */
    protected $context = [];

    /**
     * @param $context
     */
    public function __construct(array $context)
    {
        $this->context = $context;
    }

    /**
     * @param QueryBuilder $qb
     * @param string $alias
     * @param array $ignoreFields
     * @return QueryBuilder
     */
    public function applyWhere(QueryBuilder $qb, $alias, $ignoreFields = [])
    {
        foreach ($this->context as $field => $value) {
            if (in_array($field, $ignoreFields)) {
                continue;
            }
            $aliasedField = $alias.'.'.$field;
            if ($value === null) {
                $qb->andWhere($qb->expr()->isNull($aliasedField));
            } elseif ($value === self::IS_NOT_NULL) {
                $qb->andWhere($qb->expr()->isNotNull($aliasedField));
            } else {
                $paramName = $alias.'_param_'.$field;
                if (is_array($value)) {
                    $qb->andWhere($qb->expr()->in($aliasedField, ':'.$paramName));
                } else {
                    $qb->andWhere($qb->expr()->eq($aliasedField, ':'.$paramName));
                }
                $qb->setParameter($paramName, $value);
            }
        }

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @param string $alias
     * @param array $ignoreFields
     */
    public function applyToJoin(QueryBuilder $qb, $alias, $ignoreFields = [])
    {
        /** @var Join[] $joins */
        $joins = $qb->getDQLPart('join');
        $qb->resetDQLPart('join');
        $this->reapplyJoins($qb, $joins, $alias, $ignoreFields);
    }

    /**
     * @param QueryBuilder $qb
     * @param Join[] $joins
     * @param string $alias
     * @param array $ignoreFields
     */
    protected function reapplyJoins(QueryBuilder $qb, array $joins, $alias, array $ignoreFields)
    {
        foreach ($joins as $join) {
            if (is_array($join)) {
                $this->reapplyJoins($qb, $join, $alias, $ignoreFields);
                continue;
            }

            $condition = $join->getCondition();
            $usedFields = $this->getUsedFields($condition, $alias);
            $parts = [$condition];

            if ($join->getAlias() === $alias) {
                foreach ($this->context as $field => $value) {
                    if (in_array($field, $ignoreFields) || in_array($field, $usedFields)) {
                        continue;
                    }
                    $aliasedField = $alias.'.'.$field;
                    if ($value === null) {
                        $parts[] = $qb->expr()->isNull($aliasedField);
                    } elseif ($value === self::IS_NOT_NULL) {
                        $parts[] = $qb->expr()->isNotNull($aliasedField);
                    } else {
                        $paramName = $alias.'_param_'.$field;
                        $parts[] = $qb->expr()->eq($aliasedField, ':'.$paramName);
                        $qb->setParameter($paramName, $value);
                    }
                }
            }

            $condition = implode(" AND ", $parts);
            $this->applyJoinWithModifiedCondition($qb, $condition, $join);
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param string $condition
     * @param Join $join
     */
    protected function applyJoinWithModifiedCondition(QueryBuilder $qb, $condition, Join $join)
    {
        if (Join::INNER_JOIN == $join->getJoinType()) {
            $qb->innerJoin(
                $join->getJoin(),
                $join->getAlias(),
                $join->getConditionType(),
                $condition,
                $join->getIndexBy()
            );
        }
        if (Join::LEFT_JOIN == $join->getJoinType()) {
            $qb->leftJoin(
                $join->getJoin(),
                $join->getAlias(),
                $join->getConditionType(),
                $condition,
                $join->getIndexBy()
            );
        }
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->context;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->context);
    }

    /**
     * @param string $condition
     * @param string $alias
     * @return array
     */
    protected function getUsedFields($condition, $alias)
    {
        $fields = [];
        $parts = explode('AND', $condition);
        foreach ($parts as $part) {
            $matches = [];
            preg_match(sprintf('/%s\.\w+/', $alias), $part, $matches);
            foreach ($matches as $match) {
                $fields[] = explode('.', $match)[1];
            }
        }

        return $fields;
    }
}
