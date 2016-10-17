<?php

namespace Oro\Bundle\WebsiteSearchBundle\Resolver;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderExpressionVisitor;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderInterface;

class QueryPlaceholderResolver implements QueryPlaceholderResolverInterface
{
    /** @var PlaceholderInterface */
    private $placeholder;

    /**
     * @param PlaceholderInterface $placeholder
     */
    public function __construct(PlaceholderInterface $placeholder)
    {
        $this->placeholder = $placeholder;
    }

    /**
     * {@inheritdoc}
     */
    public function replace(Query $query)
    {
        $this->replaceInSelect($query);
        $this->replaceInFrom($query);
        $this->replaceInCriteria($query);
    }

    /**
     * @param Query $query
     */
    private function replaceInSelect(Query $query)
    {
        $selectAliases = $query->getSelectAliases();
        $newSelects = [];
        foreach ($query->getSelect() as $select) {
            $newSelect = $this->placeholder->replaceDefault($select);
            if (isset($selectAliases[$select])) {
                $newSelect .= ' as ' . $selectAliases[$select];
            }

            $newSelects[] = $newSelect;
        }

        $query->select($newSelects);
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function replaceInFrom(Query $query)
    {
        $newEntities = [];
        $from = $query->getFrom();

        // This check required because getFrom can return false
        if ($from) {
            foreach ($from as $alias) {
                $newEntities[] = $this->placeholder->replaceDefault($alias);
            }
        }

        return $query->from($newEntities);
    }

    /**
     * @param Query $query
     */
    private function replaceInCriteria(Query $query)
    {
        $criteria = $query->getCriteria();
        $whereExpr = $criteria->getWhereExpression();

        if ($whereExpr) {
            $visitor = new PlaceholderExpressionVisitor($this->placeholder);
            $criteria->where($visitor->dispatch($whereExpr));
        }

        $orderings = $criteria->getOrderings();
        if ($orderings) {
            foreach ($orderings as $field => $ordering) {
                unset($orderings[$field]);
                $alteredField = $this->placeholder->replaceDefault($field);
                $orderings[$alteredField] = $ordering;
            }
            $criteria->orderBy($orderings);
        }
    }
}
