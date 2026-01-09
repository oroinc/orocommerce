<?php

namespace Oro\Bundle\WebsiteSearchBundle\Query;

use Doctrine\Common\Collections\Expr\Expression;
use Oro\Bundle\SearchBundle\Engine\EngineInterface;
use Oro\Bundle\SearchBundle\Query\AbstractSearchQuery;
use Oro\Bundle\SearchBundle\Query\Query;

/**
 * Represents a website search query that executes against the website search index.
 *
 * This class extends {@see AbstractSearchQuery} to provide website-specific search functionality.
 * It encapsulates a {@see Query} object and an {@see EngineInterface} to execute searches against
 * the website search index (as opposed to the standard search index). The query supports all standard
 * search operations including field selection, filtering, ordering, and pagination. It is typically
 * created by query factories and used through {@see WebsiteSearchRepository} for type-safe search operations.
 */
class WebsiteSearchQuery extends AbstractSearchQuery
{
    /** @var EngineInterface */
    protected $engine;

    public function __construct(EngineInterface $engine, Query $query)
    {
        $this->engine = $engine;
        $this->query  = $query;
    }

    #[\Override]
    protected function query()
    {
        return $this->engine->search($this->query);
    }

    #[\Override]
    public function addWhere(Expression $expression, $type = self::WHERE_AND)
    {
        if (self::WHERE_AND === $type) {
            $this->query->getCriteria()->andWhere($expression);
        } elseif (self::WHERE_OR === $type) {
            $this->query->getCriteria()->orWhere($expression);
        }

        return $this;
    }
}
