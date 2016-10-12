<?php

namespace Oro\Bundle\InventoryBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class OroInventoryExtension extends Extension
{
    const ALIAS = 'oro_inventory';
    const VALIDATION_CONFIG = 'oro_inventory.validation.config_path';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->prependExtensionConfig($this->getAlias(), $config);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $loader->load('services.yml');
        $loader->load('form_types.yml');
        $loader->load('importexport.yml');

        $container->setParameter(
            self::VALIDATION_CONFIG,
            __DIR__ . '/../Resources/config/validation_inventory_level.yml'
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
