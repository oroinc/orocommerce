<?php

namespace Oro\Bundle\WebsiteSearchBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
        $engineParameters = $container->getParameter(self::ENGINE_PARAMETERS_KEY);
        $engineParameters = $this->processElasticSearchConnection($container, $engineParameters);
        $container->setParameter(self::ENGINE_PARAMETERS_KEY, $engineParameters);
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $engineParameters
     * @return array
     */
    protected function processElasticSearchConnection(ContainerBuilder $container, array $engineParameters)
    {
        // connection parameters
        $host = $container->getParameter(self::SEARCH_ENGINE_HOST);
        $port = $container->getParameter(self::SEARCH_ENGINE_PORT);

        if ($host && $port) {
            $host .= ':' . $port;
        }

        if ($host) {
            $engineParameters['client']['hosts'] = [$host];
        }

        // authentication parameters
        $username = $container->getParameter(self::SEARCH_ENGINE_USERNAME);
        $password = $container->getParameter(self::SEARCH_ENGINE_PASSWORD);
        $authType = $container->getParameter(self::SEARCH_ENGINE_AUTH_TYPE);

        if ($username || $password || $authType) {
            $engineParameters['client']['connectionParams']['auth'] = [$username, $password, $authType];
        }

        return $engineParameters;
    }
}
