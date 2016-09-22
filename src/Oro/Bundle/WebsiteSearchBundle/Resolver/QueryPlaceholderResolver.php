<?php

namespace Oro\Bundle\WebsiteSearchBundle\Resolver;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderExpressionVisitor;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\WebsiteSearchPlaceholderInterface;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\WebsiteSearchPlaceholderRegistry;

class QueryPlaceholderResolver implements QueryPlaceholderResolverInterface
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
     * {@inheritdoc}
     */
    public function replace(Query $query)
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
