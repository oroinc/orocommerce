<?php

namespace Oro\Bundle\WebsiteSearchBundle\Provider;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\WebsiteSearchBundle\WebsiteSearchBundle;

class SearchEngineConfigProvider
{
    const SEARCH_ENGINE_NAME      = 'search_engine_name';
    const SEARCH_ENGINE_HOST      = 'search_engine_host';
    const SEARCH_ENGINE_PORT      = 'search_engine_port';
    const SEARCH_ENGINE_USERNAME  = 'search_engine_username';
    const SEARCH_ENGINE_PASSWORD  = 'search_engine_password';
    const SEARCH_ENGINE_AUTH_TYPE = 'search_engine_auth_type';

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
        return $this->container->get(self::SEARCH_ENGINE_NAME);
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->container->get(self::SEARCH_ENGINE_HOST);
    }

    /**
     * @return string
     */
    public function getPort()
    {
        return $this->container->get(self::SEARCH_ENGINE_PORT);
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->container->get(self::SEARCH_ENGINE_USERNAME);
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->container->get(self::SEARCH_ENGINE_PASSWORD);
    }

    /**
     * @return string
     */
    public function getAuthType()
    {
        return $this->container->get(self::SEARCH_ENGINE_AUTH_TYPE);
    }
}