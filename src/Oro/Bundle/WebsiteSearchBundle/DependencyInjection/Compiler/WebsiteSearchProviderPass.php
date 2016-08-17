<?php

namespace Oro\Bundle\WebsiteSearchBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\OroWebsiteSearchExtension;

class WebsiteSearchProviderPass implements CompilerPassInterface
{
    const ENGINE_PARAMETERS_KEY   = 'oro_search.engine_parameters';

    const SEARCH_ENGINE_NAME      = 'search_engine_name';
    const SEARCH_ENGINE_HOST      = 'search_engine_host';
    const SEARCH_ENGINE_PORT      = 'search_engine_port';
    const SEARCH_ENGINE_USERNAME  = 'search_engine_username';
    const SEARCH_ENGINE_PASSWORD  = 'search_engine_password';
    const SEARCH_ENGINE_AUTH_TYPE = 'search_engine_auth_type';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->setParameter(self::SEARCH_ENGINE_HOST, $this->getConfigItem('host'));
        $container->setParameter(self::SEARCH_ENGINE_PORT, $this->getConfigItem('port'));
        $container->setParameter(self::SEARCH_ENGINE_USERNAME, $this->getConfigItem('username'));
        $container->setParameter(self::SEARCH_ENGINE_PASSWORD, $this->getConfigItem('password'));
        $container->setParameter(self::SEARCH_ENGINE_AUTH_TYPE, $this->getConfigItem('auth_type'));
    }


    protected function getConfigItem($key)
    {
        $fullKey = OroWebsiteSearchExtension::ALIAS . ConfigManager::SECTION_VIEW_SEPARATOR . $key;

        return ''; // TODO get config item
    }
}
