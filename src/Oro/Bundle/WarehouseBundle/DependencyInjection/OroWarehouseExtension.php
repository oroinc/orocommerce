<?php

namespace Oro\Bundle\WarehouseBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class OroWarehouseExtension extends Extension
{
    const ALIAS = 'oro_warehouse';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->prependExtensionConfig($this->getAlias(), $config);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $bundles = $container->getParameter('kernel.bundles');
        if (array_key_exists('OroOrderBundle', $bundles)) {
            $loader->load('order_services.yml');
        }

        $loader->load('services.yml');
        $loader->load('form_types.yml');
        $loader->load('importexport.yml');
        $loader->load('fallbacks.yml');

        $container->prependExtensionConfig($this->getAlias(), $config);
    }

    /**
     * {@inheritDoc}
     */
    public function getAlias()
    {
        return self::ALIAS;
    }
}
