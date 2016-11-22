<?php

namespace Oro\Bundle\WebsiteSearchBundle\Query\Factory;

use Oro\Bundle\SearchBundle\Datagrid\Datasource\YamlToSearchQueryConverter;
use Oro\Bundle\SearchBundle\Engine\EngineV2Interface;
use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchQuery;
use Oro\Bundle\SearchBundle\Query\Query;

class WebsiteQueryFactory implements QueryFactoryInterface
{
    /** @var EngineV2Interface */
    protected $engine;

    /**
     * @param EngineV2Interface $engine
     */
    public function __construct(EngineV2Interface $engine)
    {
        $this->engine = $engine;
    }

    /**
     * @param array $config
     * @param       $query
     */
    private function configureQuery(array $config, $query)
    {
        $builder = new YamlToSearchQueryConverter();

        $queryConfig = [];
        if (isset($config['query'])) {
            $queryConfig = ['query' => $config['query']];
        }

        $builder->process($query, $queryConfig);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $config = [])
    {
        $query = new WebsiteSearchQuery(
            $this->engine,
            new Query()
        );

        $this->configureQuery($config, $query);

        return $query;
    }
}
