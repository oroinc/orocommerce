<?php

namespace Oro\Bundle\WebsiteSearchBundle\Query\Factory;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\YamlToSearchQueryConverter;
use Oro\Bundle\SearchBundle\Engine\EngineV2Interface;
use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchQuery;

class QueryFactory implements QueryFactoryInterface
{
    /** @var EngineV2Interface */
    protected $engine;

    /** @var QueryFactory */
    protected $parent;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /**
     * @param QueryFactoryInterface    $parentQueryFactory
     * @param EventDispatcherInterface $eventDispatcher
     * @param EngineV2Interface        $engine
     */
    public function __construct(
        QueryFactoryInterface $parentQueryFactory,
        EventDispatcherInterface $eventDispatcher,
        EngineV2Interface $engine
    ) {
        $this->parent     = $parentQueryFactory;
        $this->dispatcher = $eventDispatcher;
        $this->engine     = $engine;
    }

    /**
     * {@inheritdoc}
     */
    public function create(DatagridInterface $grid, array $config)
    {
        if (!isset($config['search_index']) || $config['search_index'] !== 'website') {
            return $this->parent->create($grid, $config);
        }
        $query = new WebsiteSearchQuery($this->engine, $this->dispatcher, new Query());
        $this->configureQuery($config, $query);

        return $query;
    }

    /**
     * @param array $config
     * @param       $query
     */
    private function configureQuery(array $config, $query)
    {
        $builder = new YamlToSearchQueryConverter();

        $queryConfig = ['query' => $config['query']];
        $builder->process($query, $queryConfig);
    }
}
