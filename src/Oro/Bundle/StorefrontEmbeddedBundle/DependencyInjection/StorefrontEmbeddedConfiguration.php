<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * The schema of `Resources/config/oro/storefront_embedded.yml`.
 * A fragment defined by the route name alone is processed into the {route_name, layout_block_ids} form.
 */
class StorefrontEmbeddedConfiguration implements ConfigurationInterface
{
    public const string ROOT_NODE = 'oro_storefront_embedded';
    public const string FRAGMENTS = 'fragments';

    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $fragmentNamePattern = '/^embedded_\w+$/D';
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode(self::FRAGMENTS)
                    ->info(
                        'Embedded fragment name => storefront route that renders the page the fragment lives on, or'
                        . ' {route_name, layout_block_ids} to choose the layout blocks the fragment renders.'
                    )
                    ->useAttributeAsKey('fragment')
                    ->normalizeKeys(false)
                    ->validate()
                        ->ifTrue(static fn (array $fragments): bool => self::hasInvalidFragmentName(
                            $fragments,
                            $fragmentNamePattern
                        ))
                        ->thenInvalid(sprintf('Embedded fragment names must match "%s".', $fragmentNamePattern))
                    ->end()
                    ->validate()
                        ->always(static fn (array $fragments): array => self::withDefaultLayoutBlockIds($fragments))
                    ->end()
                    ->arrayPrototype()
                        ->beforeNormalization()
                            ->ifString()
                            ->then(static fn (string $routeName): array => ['route_name' => $routeName])
                        ->end()
                        // A bundle loaded later replaces the whole fragment definition, its layout block ids are not
                        // added to the ones of the bundle loaded earlier.
                        ->performNoDeepMerging()
                        ->children()
                            ->scalarNode('route_name')
                                ->info('The storefront route that renders the page the fragment lives on.')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->arrayNode('layout_block_ids')
                                ->info('The layout block ids the fragment renders. Defaults to the fragment name.')
                                ->scalarPrototype()->cannotBeEmpty()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    /**
     * @param array<string, array{route_name: string, layout_block_ids: string[]}> $fragments
     */
    private static function hasInvalidFragmentName(array $fragments, string $fragmentNamePattern): bool
    {
        foreach (array_keys($fragments) as $fragmentName) {
            if (!preg_match($fragmentNamePattern, (string) $fragmentName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, array{route_name: string, layout_block_ids: string[]}> $fragments
     *
     * @return array<string, array{route_name: string, layout_block_ids: string[]}>
     */
    private static function withDefaultLayoutBlockIds(array $fragments): array
    {
        foreach ($fragments as $fragmentName => $fragment) {
            if (!$fragment['layout_block_ids']) {
                $fragments[$fragmentName]['layout_block_ids'] = [(string) $fragmentName];
            }
        }

        return $fragments;
    }
}
