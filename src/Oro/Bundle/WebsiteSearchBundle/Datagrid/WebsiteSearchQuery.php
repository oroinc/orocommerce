<?php

namespace Oro\Bundle\WebsiteSearchBundle\Datagrid;

use Oro\Bundle\SearchBundle\Engine\EngineV2Interface;
use Oro\Bundle\SearchBundle\Extension\AbstractSearchQuery;
use Oro\Bundle\SearchBundle\Query\Query;

class WebsiteSearchQuery extends AbstractSearchQuery
{
    /**
     * @var EngineV2Interface
     */
    protected $engine;

    /**
     * @param EngineV2Interface $engine
     * @param Query             $query
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
}
