<?php

namespace Oro\Bundle\WebsiteSearchBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroWebsiteSearchExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);
        $container->prependExtensionConfig($this->getAlias(), SettingsBuilder::getSettings($config));

        $container->setParameter('oro_website_search.engine_dsn', $config['engine_dsn']);
        $container->setParameter('oro_website_search.engine_parameters', $config['engine_parameters']);
        $container->setParameter('oro_website_search.indexer_batch_size', $config['indexer_batch_size']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('services_api.yml');
        $loader->load('attribute_types.yml');
        $loader->load('commands.yml');
        $loader->load('website_search.yml');
        $loader->load('mq_topics.yml');
        $loader->load('mq_processors.yml');
        $loader->load('controllers.yml');
        $loader->load('import_export.yml');
        $loader->load('repositories.yml');

        if ('test' === $container->getParameter('kernel.environment')) {
            $loader->load('services_test.yml');
        }
    }
}
