<?php

namespace Oro\Bundle\CMSBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages bundle configuration
 */
class OroCMSExtension extends Extension
{
    const ALIAS = 'oro_cms';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('services_api.yml');
        $loader->load('form_types.yml');
        $loader->load('block_types.yml');
        $loader->load('controllers.yml');

        $container->prependExtensionConfig($this->getAlias(), array_intersect_key($config, array_flip(['settings'])));

        $container->setParameter(
            sprintf('%s.%s.%s', self::ALIAS, Configuration::DIRECT_EDITING, Configuration::LOGIN_PAGE_CSS_FIELD_OPTION),
            $config[Configuration::DIRECT_EDITING][Configuration::LOGIN_PAGE_CSS_FIELD_OPTION]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getAlias()
    {
        return self::ALIAS;
    }
}
