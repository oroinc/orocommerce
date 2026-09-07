<?php

namespace Oro\Bundle\StorefrontEmbeddedBundle\DependencyInjection;

use Oro\Component\Config\Loader\ContainerBuilderAdapter;
use Oro\Component\Config\Loader\Factory\CumulativeConfigLoaderFactory;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Yaml;

/**
 * Discovers the embeddable-block => route map from this bundle's layout updates
 * (`Resources/views/layouts/<theme>/<route_name>/embedded_blocks.yml`, each `@add`ing `embedded_*` blocks),
 * so adding an embeddable view is one layout update — no controller or registry edit. The per-block lists of
 * Oro routes whose client URLs must be proxied, and of routes that answer with a file rather than a page,
 * are collected from every bundle's `Resources/config/oro/storefront_embedded.yml`.
 */
class OroStorefrontEmbeddedExtension extends Extension
{
    private const string EMBEDDED_BLOCKS_FILE = 'embedded_blocks.yml';
    private const string EMBEDDED_BLOCK_ID_PATTERN = '/^embedded_\w+$/';
    private const string EMBEDDED_CONFIG = 'Resources/config/oro/storefront_embedded.yml';
    private const string PROXIED_ROUTES_SECTION = 'proxied_routes';
    private const string FILE_ROUTES_SECTION = 'file_routes';

    #[\Override]
    public function load(array $configs, ContainerBuilder $container): void
    {
        $embeddedConfig = $this->loadEmbeddedConfig($container);

        $container->setParameter('oro_storefront_embedded.blocks', $this->discoverEmbeddedBlocks($container));
        $container->setParameter(
            'oro_storefront_embedded.proxied_routes',
            $this->mergeRoutesPerBlock($embeddedConfig, self::PROXIED_ROUTES_SECTION)
        );
        $container->setParameter(
            'oro_storefront_embedded.file_routes',
            $this->mergeRoutesPerBlock($embeddedConfig, self::FILE_ROUTES_SECTION)
        );

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('controllers.yml');
        $loader->load('services.yml');
    }

    /**
     * @return array<string, string> embedded block id => route name that defines it
     */
    private function discoverEmbeddedBlocks(ContainerBuilder $container): array
    {
        $layoutsDir = realpath(__DIR__ . '/../Resources/views/layouts');
        if (!$layoutsDir) {
            return [];
        }

        $container->addResource(new DirectoryResource($layoutsDir, '/' . self::EMBEDDED_BLOCKS_FILE . '$/'));

        $blocks = [];
        foreach (glob($layoutsDir . '/*/*/' . self::EMBEDDED_BLOCKS_FILE) as $file) {
            $container->addResource(new FileResource($file));
            $routeName = basename(\dirname($file));

            $actions = Yaml::parseFile($file)['layout']['actions'] ?? [];
            foreach ($actions as $action) {
                $blockId = $action['@add']['id'] ?? null;
                if (!\is_string($blockId) || !preg_match(self::EMBEDDED_BLOCK_ID_PATTERN, $blockId)) {
                    continue;
                }
                if (isset($blocks[$blockId]) && $blocks[$blockId] !== $routeName) {
                    throw new \LogicException(sprintf(
                        'Embedded block "%s" is defined for both "%s" and "%s" routes; block ids must be unique.',
                        $blockId,
                        $blocks[$blockId],
                        $routeName
                    ));
                }
                $blocks[$blockId] = $routeName;
            }
        }

        return $blocks;
    }

    /**
     * @return array<int, array> the `oro_storefront_embedded` section of every bundle that declares one
     */
    private function loadEmbeddedConfig(ContainerBuilder $container): array
    {
        $configLoader = CumulativeConfigLoaderFactory::create('oro_storefront_embedded', self::EMBEDDED_CONFIG);

        return array_map(
            static fn ($resource): array => $resource->data['oro_storefront_embedded'] ?? [],
            $configLoader->load(new ContainerBuilderAdapter($container))
        );
    }

    /**
     * @param array<int, array> $embeddedConfig
     *
     * @return array<string, string[]> embedded block id => route names that section names for it
     */
    private function mergeRoutesPerBlock(array $embeddedConfig, string $section): array
    {
        $routesPerBlock = [];
        foreach ($embeddedConfig as $config) {
            foreach ($config[$section] ?? [] as $blockId => $routes) {
                $routesPerBlock[$blockId] = array_values(array_unique(
                    array_merge($routesPerBlock[$blockId] ?? [], (array) $routes)
                ));
            }
        }

        return $routesPerBlock;
    }
}
