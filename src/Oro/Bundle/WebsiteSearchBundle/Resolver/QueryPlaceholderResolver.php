<?php

namespace Oro\Bundle\WebsiteSearchBundle\Resolver;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderExpressionVisitor;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\WebsiteSearchPlaceholderInterface;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\WebsiteSearchPlaceholderRegistry;

class QueryPlaceholderResolver
{
    /**
     * @var WebsiteSearchPlaceholderRegistry
     */
    private $placeholderRegistry;

    /**
     * @param WebsiteSearchPlaceholderRegistry $placeholderRegistry
     */
    public function __construct(WebsiteSearchPlaceholderRegistry $placeholderRegistry)
    {
        $this->placeholderRegistry = $placeholderRegistry;
    }

    /**
     * @param Query $query
     * @param array $context
     * @return Query
     */
    public function replace(Query $query, array $context)
    {
        foreach ($this->placeholderRegistry->getPlaceholders() as $placeholder) {
            $this->replaceInSelect($query, $placeholder);
            $this->replaceInFrom($query, $placeholder);
            $this->replaceInCriteria($query, $placeholder);
        }

        return $query;
    }

    /**
     * @param Query $query
     * @param WebsiteSearchPlaceholderInterface $placeholder
     */
    private function replaceInSelect(Query $query, WebsiteSearchPlaceholderInterface $placeholder)
    {
        $selects = $query->getSelect();
        $selectAliases = $query->getSelectAliases();
        $newSelects = [];
        foreach ($query->getSelect() as $select) {
            $newSelect = str_replace($placeholder->getPlaceholder(), $placeholder->getValue(), $select);
            if (isset($selectAliases[$select])) {
                $newSelect .= ' as ' . $selectAliases[$select];
            }

            $newSelects[] = $newSelect;
        }

        $query->select($newSelects);
    }

    /**
     * @param Query $query
     * @param WebsiteSearchPlaceholderInterface $placeholder
     * @return Query
     */
    private function replaceInFrom(Query $query, WebsiteSearchPlaceholderInterface $placeholder)
    {
        $newEntities = [];
        $from = $query->getFrom();

        // This check required because getFrom can return false
        if ($from) {
            foreach ($from as $alias) {
                $newEntities[] = str_replace($placeholder->getPlaceholder(), $placeholder->getValue(), $alias);
            }
        }

        return $query->from($newEntities);
    }

    /**
     * @param Query $query
     * @param WebsiteSearchPlaceholderInterface $placeholder
     */
    private function replaceInCriteria(Query $query, WebsiteSearchPlaceholderInterface $placeholder)
    {
        $criteria = $query->getCriteria();
        $whereExpr = $criteria->getWhereExpression();

        if ($whereExpr) {
            $visitor = new PlaceholderExpressionVisitor($placeholder);
            $criteria->where($visitor->dispatch($whereExpr));
        }
    }
}
