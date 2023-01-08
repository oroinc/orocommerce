<?php

namespace Oro\Bundle\WebsiteSearchBundle\Resolver;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderExpressionVisitor;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderInterface;

/**
 * Provides functionality to replace placeholders with their values in field names in all parts of a search query.
 */
class QueryPlaceholderResolver implements QueryPlaceholderResolverInterface
{
    /** @var PlaceholderInterface */
    private $placeholder;

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
        $this->replaceInAggregations($query);
    }

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

    private function replaceInAggregations(Query $query)
    {
        $aggregations = $query->getAggregations();
        if (!$aggregations) {
            return;
        }

        $newAggregations = [];
        foreach ($aggregations as $name => $item) {
            $newAggregations[$name] = [
                'field' => $this->placeholder->replaceDefault($item['field']),
                'function' => $item['function'],
                'parameters' => $item['parameters']
            ];
        }
        $query->setAggregations($newAggregations);
    }
}
