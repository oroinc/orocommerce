<?php

namespace Oro\Bundle\WebsiteSearchBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\OroWebsiteSearchExtension;

class WebsiteSearchBundle extends Bundle
{
    const ENGINE_PARAMETERS_KEY   = 'oro_search.engine_parameters';

    const SEARCH_ENGINE_NAME      = 'search_engine_name';
    const SEARCH_ENGINE_HOST      = 'search_engine_host';
    const SEARCH_ENGINE_PORT      = 'search_engine_port';
    const SEARCH_ENGINE_USERNAME  = 'search_engine_username';
    const SEARCH_ENGINE_PASSWORD  = 'search_engine_password';
    const SEARCH_ENGINE_AUTH_TYPE = 'search_engine_auth_type';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        return new OroWebsiteSearchExtension();
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->configManager = $this->container->get('oro_config.manager');

        $this->container->setParameter(self::SEARCH_ENGINE_HOST, $this->getConfigItem('host'));
        $this->container->setParameter(self::SEARCH_ENGINE_PORT, $this->getConfigItem('port'));
        $this->container->setParameter(self::SEARCH_ENGINE_USERNAME, $this->getConfigItem('username'));
        $this->container->setParameter(self::SEARCH_ENGINE_PASSWORD, $this->getConfigItem('password'));
        $this->container->setParameter(self::SEARCH_ENGINE_AUTH_TYPE, $this->getConfigItem('auth_type'));
    }

    /**
     * @param string $key
     * @return mixed
     */
    protected function getConfigItem($key)
    {
        $fullKey = OroWebsiteSearchExtension::ALIAS . ConfigManager::SECTION_VIEW_SEPARATOR . $key;

        return $this->configManager->get($fullKey);
    }
}
