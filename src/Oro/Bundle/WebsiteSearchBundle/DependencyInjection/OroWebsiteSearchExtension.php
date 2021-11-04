<?php

namespace Oro\Bundle\WebsiteSearchBundle\DependencyInjection;

use Oro\Component\Config\Loader\ContainerBuilderAdapter;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\NullCumulativeFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroWebsiteSearchExtension extends Extension
{
    const ALIAS = 'oro_website_search';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('oro_website_search.engine', $config['engine']);
        $container->setParameter('oro_website_search.engine_parameters', $config['engine_parameters']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('services_api.yml');
        $loader->load('attribute_types.yml');
        $loader->load('commands.yml');

        $configLoader = new CumulativeConfigLoader(
            'oro_website_search',
            new NullCumulativeFileLoader('Resources/config/oro/website_search_engine/' . $config['engine'] . '.yml')
        );
        $resources = $configLoader->load(new ContainerBuilderAdapter($container));
        foreach ($resources as $resource) {
            $loader->load($resource->path);
        }

        if ($container->getParameter('kernel.environment') === 'test') {
            $loader->load('services_test.yml');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return self::ALIAS;
    }
}
