<?php

namespace Oro\Bundle\WebsiteSearchBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class WebsiteSearchEngineConfigPass implements CompilerPassInterface
{
    const ENGINE_KEY      = 'oro_website_search.engine';
    const ENGINE_PARAMETERS_KEY   = 'oro_website_search.engine_parameters';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $engineParameters = $container->getParameter(self::ENGINE_PARAMETERS_KEY);
        $engineParameters = $this->processEngineConfig($container, $engineParameters);
        $container->setParameter(self::ENGINE_PARAMETERS_KEY, $engineParameters);
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $engineParameters
     * @return array
     */
    protected function processEngineConfig(ContainerBuilder $container, array $engineParameters)
    {
        // connection parameters
        $host = $container->getParameter(self::SEARCH_ENGINE_HOST);
        $port = $container->getParameter(self::SEARCH_ENGINE_PORT);

        $username = $container->getParameter(self::SEARCH_ENGINE_USERNAME);
        $password = $container->getParameter(self::SEARCH_ENGINE_PASSWORD);

        if ($host) {
            if ($username) {
                $host = $this->addAuthenticationToHost(
                    $host,
                    $username,
                    $password
                );
            }

            if ($port) {
                $host .= ':' . $port;
            }

            $engineParameters['client'][ClientFactory::OPTION_HOSTS] = [$host];
        }

        return $this->addSSLParameters($container, $engineParameters);
    }

    /**
     * @param string $host
     * @param string $username
     * @param string $password
     * @return string
     */
    protected function addAuthenticationToHost($host, $username, $password)
    {
        $authPart = $username.':'.$password.'@';

        if (false === strpos($host, '://')) {
            $host = $authPart . $host;
        } else {
            $host = str_replace('://', '://' . $authPart, $host);
        }

        return $host;
    }

    /**
     * @param ContainerBuilder $container
     * @param array $engineParameters
     * @return array
     */
    protected function addSSLParameters(ContainerBuilder $container, array $engineParameters)
    {
        $engineParameters = $this->addSSLVerificationParameter($container, $engineParameters);
        $engineParameters = $this->addSSLCertParameter($container, $engineParameters);
        $engineParameters = $this->addSSLKeyParameter($container, $engineParameters);

        return $engineParameters;
    }

    /**
     * @param ContainerBuilder $container
     * @param array $engineParameters
     * @return array
     */
    protected function addSSLVerificationParameter(ContainerBuilder $container, array $engineParameters)
    {
        if ($container->hasParameter(self::SEARCH_ENGINE_SSL_VERIFICATION)) {
            $sslVerification = $container->getParameter(self::SEARCH_ENGINE_SSL_VERIFICATION);

            if ($sslVerification) {
                $engineParameters['client'][ClientFactory::OPTION_SSL_VERIFICATION] = $sslVerification;
            }
        }

        return $engineParameters;
    }

    /**
     * @param ContainerBuilder $container
     * @param array $engineParameters
     * @return array
     */
    protected function addSSLCertParameter(ContainerBuilder $container, array $engineParameters)
    {
        if ($container->hasParameter(self::SEARCH_ENGINE_SSL_CERT)) {
            $sslCert = $container->getParameter(self::SEARCH_ENGINE_SSL_CERT);

            if ($sslCert) {
                $sslCertPassword = $container->hasParameter(self::SEARCH_ENGINE_SSL_CERT_PASSWORD)
                    ? $container->getParameter(self::SEARCH_ENGINE_SSL_CERT_PASSWORD)
                    : null;

                $engineParameters['client'][ClientFactory::OPTION_SSL_CERT] = [$sslCert, $sslCertPassword];
            }
        }

        return $engineParameters;
    }

    /**
     * @param ContainerBuilder $container
     * @param array $engineParameters
     * @return array
     */
    protected function addSSLKeyParameter(ContainerBuilder $container, array $engineParameters)
    {
        if ($container->hasParameter(self::SEARCH_ENGINE_SSL_KEY)) {
            $sslKey = $container->getParameter(self::SEARCH_ENGINE_SSL_KEY);

            if ($sslKey) {
                $sslKeyPassword = $container->hasParameter(self::SEARCH_ENGINE_SSL_KEY_PASSWORD)
                    ? $container->getParameter(self::SEARCH_ENGINE_SSL_KEY_PASSWORD)
                    : null;

                $engineParameters['client'][ClientFactory::OPTION_SSL_KEY] = [$sslKey, $sslKeyPassword];
            }
        }

        return $engineParameters;
    }
}
