<?php

namespace Oro\Bundle\WebsiteSearchBundle\Query\Factory;

use Oro\Bundle\SearchBundle\Datagrid\Datasource\YamlToSearchQueryConverter;
use Oro\Bundle\SearchBundle\Engine\EngineV2Interface;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchQuery;

trait WebsiteQueryFactoryTrait
{
    /** @var EngineV2Interface */
    protected $engine;

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
     * @param array $config
     * @return WebsiteSearchQuery
     */
    protected function createWebsiteSearchQuery(array $config)
    {
        $query = new WebsiteSearchQuery(
            $this->engine,
            new Query()
        );

        $this->configureQuery($config, $query);

        return $query;
    }
}