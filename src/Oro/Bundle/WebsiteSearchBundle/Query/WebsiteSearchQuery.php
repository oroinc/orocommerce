<?php

namespace Oro\Bundle\WebsiteSearchBundle\Query;

use Doctrine\Common\Collections\Expr\Expression;

use Oro\Bundle\SearchBundle\Query\AbstractSearchQuery;
use Oro\Bundle\SearchBundle\Engine\EngineV2Interface;
use Oro\Bundle\SearchBundle\Query\Query;

class WebsiteSearchQuery extends AbstractSearchQuery
{
    /** @var EngineV2Interface */
    protected $engine;

    /**
     * @param EngineV2Interface $engine
     * @param Query $query
     */
    public function __construct(EngineV2Interface $engine, Query $query)
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
