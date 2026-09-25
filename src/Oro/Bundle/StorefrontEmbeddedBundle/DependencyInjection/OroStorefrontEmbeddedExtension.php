<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Sets the embedded block map and the per-block route lists collected from every bundle's
 * `Resources/config/oro/storefront_embedded.yml` as container parameters, and loads the bundle services.
 */
class OroStorefrontEmbeddedExtension extends Extension
{
    public const string BLOCKS_PARAMETER = 'oro_storefront_embedded.blocks';
    public const string PROXIED_ROUTES_PARAMETER = 'oro_storefront_embedded.proxied_routes';
    public const string FILE_ROUTES_PARAMETER = 'oro_storefront_embedded.file_routes';

    #[\Override]
    public function load(array $configs, ContainerBuilder $container): void
    {
        $embeddedConfig = (new StorefrontEmbeddedConfigurationLoader())->load($container);

        $container->setParameter(
            self::BLOCKS_PARAMETER,
            $embeddedConfig[StorefrontEmbeddedConfiguration::BLOCKS]
        );
        $container->setParameter(
            self::PROXIED_ROUTES_PARAMETER,
            $embeddedConfig[StorefrontEmbeddedConfiguration::PROXIED_ROUTES]
        );
        $container->setParameter(
            self::FILE_ROUTES_PARAMETER,
            $embeddedConfig[StorefrontEmbeddedConfiguration::FILE_ROUTES]
        );

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('controllers.yml');
        $loader->load('services.yml');
    }
}
