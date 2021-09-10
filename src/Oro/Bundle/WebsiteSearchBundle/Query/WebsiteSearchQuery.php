<?php

namespace Oro\Bundle\WebsiteSearchBundle\Query;

use Doctrine\Common\Collections\Expr\Expression;
use Oro\Bundle\SearchBundle\Engine\EngineInterface;
use Oro\Bundle\SearchBundle\Query\AbstractSearchQuery;
use Oro\Bundle\SearchBundle\Query\Query;

class WebsiteSearchQuery extends AbstractSearchQuery
{
    /** @var EngineInterface */
    protected $engine;

    public function __construct(EngineInterface $engine, Query $query)
    {
        $this->engine = $engine;
        $this->query  = $query;
    }

    /**
     * {@inheritdoc}
     */
    protected function query()
    {
        return $this->engine->search($this->query);
    }

    /**
     * {@inheritdoc}
     */
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
