<?php

namespace Oro\Bundle\WebsiteSearchBundle\DependencyInjection;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
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
        $loader->load('attribute_types.yml');
        $loader->load('commands.yml');

        $ymlLoader = new YamlCumulativeFileLoader(
            'Resources/config/oro/website_search_engine/' . $config['engine'] . '.yml'
        );

        $engineLoader = new CumulativeConfigLoader('oro_website_search', $ymlLoader);
        $engineResources = $engineLoader->load($container);

        foreach ($engineResources as $engineResource) {
            $loader->load($engineResource->path);
        }

        if ('test' === $container->getParameter('kernel.environment')) {
            $this->configureTestEnvironment($container);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return self::ALIAS;
    }

    /**
     * @param ContainerBuilder $container
     */
    private function configureTestEnvironment(ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Tests/Functional/Environment')
        );
        $loader->load('services.yml');
    }
}
