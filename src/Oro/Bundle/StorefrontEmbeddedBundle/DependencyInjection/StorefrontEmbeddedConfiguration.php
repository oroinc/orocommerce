<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\DependencyInjection;

use Oro\Bundle\StorefrontEmbeddedBundle\Controller\Frontend\EmbeddedFragmentController;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * The schema of `Resources/config/oro/storefront_embedded.yml`, the configuration every bundle may ship to make
 * its storefront pages embeddable: the embedded block => route map, the routes whose client urls the host proxies
 * and the routes that answer with a file rather than a page, both per embedded block or for `any_block`.
 */
class StorefrontEmbeddedConfiguration implements ConfigurationInterface
{
    public const string ROOT_NODE = 'oro_storefront_embedded';
    public const string BLOCKS = 'blocks';
    public const string PROXIED_ROUTES = 'proxied_routes';
    public const string FILE_ROUTES = 'file_routes';

    private const string BLOCK_KEY = 'block';
    private const string BLOCK_ID_PATTERN = '/^' . EmbeddedFragmentController::BLOCK_ID_REQUIREMENT . '$/';

    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode(self::BLOCKS)
                    ->info('Embedded block id => storefront route that renders the page the block lives on.')
                    ->useAttributeAsKey(self::BLOCK_KEY)
                    ->normalizeKeys(false)
                    ->validate()
                        ->ifTrue(static fn (array $blocks): bool => self::hasInvalidBlockId($blocks))
                        ->thenInvalid(sprintf('Embedded block ids must match "%s".', self::BLOCK_ID_PATTERN))
                    ->end()
                    ->scalarPrototype()->cannotBeEmpty()->end()
                ->end()
                ->arrayNode(self::PROXIED_ROUTES)
                    ->info('Embedded block id (or any_block) => routes whose client urls the host must proxy.')
                    ->useAttributeAsKey(self::BLOCK_KEY)
                    ->normalizeKeys(false)
                    ->arrayPrototype()
                        ->scalarPrototype()->cannotBeEmpty()->end()
                    ->end()
                ->end()
                ->arrayNode(self::FILE_ROUTES)
                    ->info('Embedded block id (or any_block) => routes that answer with a file, not a page.')
                    ->useAttributeAsKey(self::BLOCK_KEY)
                    ->normalizeKeys(false)
                    ->arrayPrototype()
                        ->scalarPrototype()->cannotBeEmpty()->end()
                    ->end()
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
            if (!preg_match(self::BLOCK_ID_PATTERN, (string) $blockId)) {
                return true;
            }
        }

        return false;
    }
}
