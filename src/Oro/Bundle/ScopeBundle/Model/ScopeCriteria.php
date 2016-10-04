<?php

namespace Oro\Bundle\ScopeBundle\Model;

use Doctrine\ORM\QueryBuilder;

class ScopeCriteria
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
     *
     * @return QueryBuilder
     */
    public function applyWhere(QueryBuilder $qb, $alias = 'scope')
    {
        foreach ($this->context as $field => $value) {
            $aliasedField = $alias.'.'.$field;
            if ($value === null) {
                $qb->andWhere($qb->expr()->isNull($aliasedField));
            } else {
                if ($value === self::IS_NOT_NULL) {
                    $qb->andWhere($qb->expr()->isNotNull($aliasedField));
                } else {
                    $paramName = $alias.'_param_'.$field;
                    $qb->andWhere($qb->expr()->eq($aliasedField, ':'.$paramName));
                    $qb->setParameter($paramName, $value);
                }
            }
        }

        return $qb;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->context;
    }
}
