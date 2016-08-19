<?php

namespace Oro\Bundle\WebsiteSearchBundle\Factory;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\SearchBundle\Engine\EngineV2Interface;
use Oro\Bundle\SearchBundle\Factory\QueryFactoryInterface;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Datagrid\WebsiteSearchQuery;

class QueryFactory implements QueryFactoryInterface
{
    /**
     * @var EngineV2Interface
     */
    protected $engine;

    /** @var QueryFactory */
    protected $parent;

    /**
     * @param QueryFactoryInterface $parentQueryFactory
     * @param EngineV2Interface     $engine
     */
    public function __construct(
        QueryFactoryInterface $parentQueryFactory,
        EngineV2Interface $engine
    ) {
        $this->parent = $parentQueryFactory;
        $this->engine = $engine;
    }

    /**
     * {@inheritdoc}
     */
    public function create(DatagridInterface $grid, array $config)
    {
        if (isset($config['search_index']) && $config['search_index'] === 'website') {
            return new WebsiteSearchQuery($this->engine, new Query());
        }

        return $this->parent->create($grid, $config);
    }
}
