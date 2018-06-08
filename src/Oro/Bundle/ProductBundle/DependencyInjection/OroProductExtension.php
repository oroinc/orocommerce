<?php

namespace Oro\Bundle\ProductBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroProductExtension extends Extension
{
    const ALIAS = 'oro_product';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('form_types.yml');
        $loader->load('importexport.yml');
        $loader->load('block_types.yml');
        $loader->load('expression_services.yml');
        $loader->load('services_api.yml');
        $loader->load('system_configuration_services.yml');
        $loader->load('related_items.yml');
        $loader->load('commands.yml');

        if ('test' === $container->getParameter('kernel.environment')) {
            $this->configureTestEnvironment($container);
        }

        $container->prependExtensionConfig($this->getAlias(), $config);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function configureTestEnvironment(ContainerBuilder $container)
    {
        // Creating alias for a private service to customise parameters
        $testEntityAliasProviderDef = new Definition(
            'oro_product.importexport.normalizer.product_image.test'
        );
        $container->setDefinition(
            'oro_product.importexport.normalizer.product_image.test',
            $testEntityAliasProviderDef
        );
        $container->setAlias(
            'oro_product.importexport.normalizer.product_image.test',
            new Alias('oro_product.importexport.normalizer.product_image')
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
