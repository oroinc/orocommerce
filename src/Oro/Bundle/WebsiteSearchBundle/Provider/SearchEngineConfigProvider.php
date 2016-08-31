<?php

namespace Oro\Bundle\WebsiteSearchBundle\Provider;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\WebsiteSearchBundle\WebsiteSearchBundle;

class SearchEngineConfigProvider
{
    const SEARCH_ENGINE_NAME      = 'oro_website_search.engine_name';

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
     * @return string
     */
    public function getEngineName()
    {
        return $this->container->getParameter(self::SEARCH_ENGINE_NAME);
    }
}
