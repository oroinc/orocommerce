<?php

namespace Oro\Bundle\WebsiteSearchBundle\Provider;

use Symfony\Component\DependencyInjection\ContainerInterface;

class SearchEnginesProvider
{
    const SEARCH_ENGINE_ORM = 'ORM';
    const SEARCH_ENGINE_ES = 'Elastic Search';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return array
     */
    public function getEngines()
    {
        $engines = [self::SEARCH_ENGINE_ORM];

        if ($this->container->has('oropro_elasticsearch.engine.index_agent')) {
            $engines[] = self::SEARCH_ENGINE_ES;
        }

        return $engines;
    }
}