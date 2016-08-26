<?php

namespace Oro\Bundle\WebsiteSearchBundle\Provider;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\WebsiteSearchBundle\WebsiteSearchBundle;

class SearchEngineConfigProvider
{
    const SEARCH_ENGINE_NAME      = 'oro_website_search.engine_name';
    const SEARCH_ENGINE_HOST      = 'oro_website_search.engine_parameters.host';
    const SEARCH_ENGINE_PORT      = 'oro_website_search.engine_parameters.port';
    const SEARCH_ENGINE_USERNAME  = 'oro_website_search.engine_parameters.username';
    const SEARCH_ENGINE_PASSWORD  = 'oro_website_search.engine_parameters.password';
    const SEARCH_ENGINE_AUTH_TYPE = 'oro_website_search.engine_parameters.auth_type';

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

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->container->getParameter(self::SEARCH_ENGINE_HOST);
    }

    /**
     * @return string
     */
    public function getPort()
    {
        return $this->container->getParameter(self::SEARCH_ENGINE_PORT);
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->container->getParameter(self::SEARCH_ENGINE_USERNAME);
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->container->getParameter(self::SEARCH_ENGINE_PASSWORD);
    }

    /**
     * @return string
     */
    public function getAuthType()
    {
        return $this->container->getParameter(self::SEARCH_ENGINE_AUTH_TYPE);
    }
}