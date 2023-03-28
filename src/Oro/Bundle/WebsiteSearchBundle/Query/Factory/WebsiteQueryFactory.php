<?php

namespace Oro\Bundle\WebsiteSearchBundle\Query\Factory;

use Oro\Bundle\SearchBundle\Datagrid\Datasource\YamlToSearchQueryConverter;
use Oro\Bundle\SearchBundle\Engine\EngineInterface;
use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchQuery;

/**
 * Creates and configures WebsiteSearchQuery instances.
 */
class WebsiteQueryFactory implements QueryFactoryInterface
{
    /** @var EngineInterface */
    protected $engine;

    public function __construct(EngineInterface $engine)
    {
        $this->engine = $engine;
    }

    private function configureQuery(array $config, $query)
    {
        $builder = new YamlToSearchQueryConverter();

        $queryConfig = [
            'query' => $config['query'] ?? [],
            'hints' => $config['hints'] ?? []
        ];

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
