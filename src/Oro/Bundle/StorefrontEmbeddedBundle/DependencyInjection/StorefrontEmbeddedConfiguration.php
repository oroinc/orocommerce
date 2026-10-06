<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\DependencyInjection;

use Oro\Bundle\StorefrontEmbeddedBundle\Controller\Frontend\EmbeddedFragmentController;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * The schema of `Resources/config/oro/storefront_embedded.yml`, the configuration every bundle may ship to make
 * its storefront pages embeddable: the embedded block => route map.
 */
class StorefrontEmbeddedConfiguration implements ConfigurationInterface
{
    public const string ROOT_NODE = 'oro_storefront_embedded';
    public const string BLOCKS = 'blocks';

    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode(self::BLOCKS)
                    ->info('Embedded block id => storefront route that renders the page the block lives on.')
                    ->useAttributeAsKey('block')
                    ->normalizeKeys(false)
                    ->validate()
                        ->ifTrue(static fn (array $blocks): bool => self::hasInvalidBlockId($blocks))
                        ->thenInvalid(sprintf(
                            'Embedded block ids must match "%s".',
                            '/^' . EmbeddedFragmentController::BLOCK_ID_REQUIREMENT . '$/'
                        ))
                    ->end()
                    ->scalarPrototype()->cannotBeEmpty()->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    /**
     * @param array<string, string> $blocks
     */
    private static function hasInvalidBlockId(array $blocks): bool
    {
        foreach (array_keys($blocks) as $blockId) {
            if (!preg_match('/^' . EmbeddedFragmentController::BLOCK_ID_REQUIREMENT . '$/', (string) $blockId)) {
                return true;
            }
        }

        return false;
    }
}
