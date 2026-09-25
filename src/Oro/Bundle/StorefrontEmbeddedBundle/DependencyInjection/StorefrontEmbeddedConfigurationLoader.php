<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\DependencyInjection;

use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Component\Config\Loader\ContainerBuilderAdapter;
use Oro\Component\Config\Loader\CumulativeConfigProcessorUtil;
use Oro\Component\Config\Loader\Factory\CumulativeConfigLoaderFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Loads `Resources/config/oro/storefront_embedded.yml` from every bundle, validates each against
 * StorefrontEmbeddedConfiguration and merges them into the one configuration the container parameters are set
 * from. An embedded block id has to name one route across all bundles, which is checked before the merge because
 * the merge itself would let the last bundle win silently.
 */
class StorefrontEmbeddedConfigurationLoader
{
    public const string CONFIG_FILE = 'Resources/config/oro/storefront_embedded.yml';

    /**
     * @return array{
     *     blocks: array<string, string>,
     *     proxied_routes: array<string, string[]>,
     *     file_routes: array<string, string[]>
     * }
     */
    public function load(ContainerBuilder $container): array
    {
        $configs = $this->loadConfigs($container);
        $this->assertBlockIdsAreUnique($configs);

        $config = CumulativeConfigProcessorUtil::processConfiguration(
            self::CONFIG_FILE,
            new StorefrontEmbeddedConfiguration(),
            $configs
        );

        return [
            StorefrontEmbeddedConfiguration::BLOCKS => $config[StorefrontEmbeddedConfiguration::BLOCKS],
            StorefrontEmbeddedConfiguration::PROXIED_ROUTES => $this->uniqueRoutesPerBlock(
                $config[StorefrontEmbeddedConfiguration::PROXIED_ROUTES]
            ),
            StorefrontEmbeddedConfiguration::FILE_ROUTES => $this->uniqueRoutesPerBlock(
                $config[StorefrontEmbeddedConfiguration::FILE_ROUTES]
            ),
        ];
    }

    /**
     * @return array<int, array<string, mixed>> the `oro_storefront_embedded` section of every bundle that has one
     */
    private function loadConfigs(ContainerBuilder $container): array
    {
        $configLoader = CumulativeConfigLoaderFactory::create(
            StorefrontEmbeddedConfiguration::ROOT_NODE,
            self::CONFIG_FILE
        );

        return array_map(
            static fn (CumulativeResourceInfo $resource): array => $resource->data[
                StorefrontEmbeddedConfiguration::ROOT_NODE
            ] ?? [],
            $configLoader->load(new ContainerBuilderAdapter($container))
        );
    }

    /**
     * @param array<int, array<string, mixed>> $configs
     *
     * @throws \LogicException when two bundles map the same embedded block id to different routes
     */
    private function assertBlockIdsAreUnique(array $configs): void
    {
        $blocks = [];
        foreach ($configs as $config) {
            foreach ((array) ($config[StorefrontEmbeddedConfiguration::BLOCKS] ?? []) as $blockId => $routeName) {
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
    }

    /**
     * @param array<string, string[]> $routesPerBlock
     *
     * @return array<string, string[]>
     */
    private function uniqueRoutesPerBlock(array $routesPerBlock): array
    {
        return array_map(
            static fn (array $routes): array => array_values(array_unique($routes)),
            $routesPerBlock
        );
    }
}
