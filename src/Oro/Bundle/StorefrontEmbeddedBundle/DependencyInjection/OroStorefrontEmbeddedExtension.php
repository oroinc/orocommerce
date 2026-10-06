<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Sets the embedded block map collected from every bundle's `Resources/config/oro/storefront_embedded.yml`
 * as a container parameter, and loads the bundle services.
 */
class OroStorefrontEmbeddedExtension extends Extension
{
    #[\Override]
    public function load(array $configs, ContainerBuilder $container): void
    {
        $embeddedConfig = (new StorefrontEmbeddedConfigurationLoader())->load($container);

        $container->setParameter(
            'oro_storefront_embedded.blocks',
            $embeddedConfig[StorefrontEmbeddedConfiguration::BLOCKS]
        );

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('controllers.yml');
        $loader->load('services.yml');
    }
}
