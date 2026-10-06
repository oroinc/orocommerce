<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\DependencyInjection;

use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Component\Config\Loader\ContainerBuilderAdapter;
use Oro\Component\Config\Loader\CumulativeConfigProcessorUtil;
use Oro\Component\Config\Loader\Factory\CumulativeConfigLoaderFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Merges `Resources/config/oro/storefront_embedded.yml` of all bundles. When several bundles define the same
 * embedded fragment, the definition of the bundle loaded last wins as a whole.
 */
final class StorefrontEmbeddedConfigurationLoader
{
    /**
     * @return array{
     *     fragments: array<string, array{route_name: string, layout_block_ids: string[]}>
     * }
     */
    public function load(ContainerBuilder $container): array
    {
        $configs = $this->loadConfigs($container);

        $config = CumulativeConfigProcessorUtil::processConfiguration(
            'Resources/config/oro/storefront_embedded.yml',
            new StorefrontEmbeddedConfiguration(),
            $configs
        );

        return [
            StorefrontEmbeddedConfiguration::FRAGMENTS => $config[StorefrontEmbeddedConfiguration::FRAGMENTS],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadConfigs(ContainerBuilder $container): array
    {
        $configLoader = CumulativeConfigLoaderFactory::create(
            StorefrontEmbeddedConfiguration::ROOT_NODE,
            'Resources/config/oro/storefront_embedded.yml'
        );

        return array_map(
            static fn (CumulativeResourceInfo $resource): array => $resource->data[
                StorefrontEmbeddedConfiguration::ROOT_NODE
            ] ?? [],
            $configLoader->load(new ContainerBuilderAdapter($container))
        );
    }
}
