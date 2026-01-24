<?php

namespace Oro\Bundle\WebsiteSearchBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers placeholder services with the placeholder registry during dependency injection container compilation.
 *
 * This compiler pass collects all services tagged with 'website_search.placeholder' and registers them
 * with the {@see PlaceholderRegistry}. Placeholders are used throughout the website search system
 * to dynamically replace tokens in field names and index aliases with context-specific values.
 */
class WebsiteSearchCompilerPass implements CompilerPassInterface
{
    const WEBSITE_SEARCH_PLACEHOLDER_REGISTRY = 'oro_website_search.placeholder.registry';
    const WEBSITE_SEARCH_PLACEHOLDER_TAG = 'website_search.placeholder';

    #[\Override]
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(self::WEBSITE_SEARCH_PLACEHOLDER_REGISTRY)) {
            return;
        }

        $placeholderRegistryDefinition = $container->getDefinition(self::WEBSITE_SEARCH_PLACEHOLDER_REGISTRY);
        $taggedPlaceholders = $container->findTaggedServiceIds(self::WEBSITE_SEARCH_PLACEHOLDER_TAG);
        foreach ($taggedPlaceholders as $id => $tags) {
            $placeholderRegistryDefinition->addMethodCall(
                'addPlaceholder',
                [new Reference($id)]
            );
        }
    }
}
