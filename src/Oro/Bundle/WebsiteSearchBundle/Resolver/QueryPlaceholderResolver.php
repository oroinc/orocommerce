<?php

namespace Oro\Bundle\WebsiteSearchBundle\Resolver;

use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
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
            $this->replaceInFrom($query, $placeholder);
            $this->replaceInCriteria($query, $placeholder);
        }

        return $query;
    }

    /**
     * @param Query $query
     * @param WebsiteSearchPlaceholderInterface $placeholder
     * @return Query
     */
    private function replaceInFrom(Query $query, WebsiteSearchPlaceholderInterface $placeholder)
    {
        $entities = [];

        $from = $query->getFrom();
        if ($from) {
            foreach ($from as $alias) {
                $alias = str_replace($placeholder->getPlaceholder(), $placeholder->getValue(), $alias);

                $entities[] = $alias;
            }
        }
        return $query->from($entities);
    }

    /**
     * @param Query $query
     * @param WebsiteSearchPlaceholderInterface $placeholder
     */
    private function replaceInCriteria(Query $query, WebsiteSearchPlaceholderInterface $placeholder)
    {
        /** @var Criteria $criteria */
        $criteria = $query->getCriteria();

        $whereExpr = $criteria->getWhereExpression();
        if ($whereExpr) {
            $visitor = new PlaceholderExpressionVisitor($placeholder);
            $criteria->where($visitor->dispatch($whereExpr));
        }
    }
}
