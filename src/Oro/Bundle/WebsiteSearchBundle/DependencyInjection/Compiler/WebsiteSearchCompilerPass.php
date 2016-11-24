<?php

namespace Oro\Bundle\WebsiteSearchBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class WebsiteSearchCompilerPass implements CompilerPassInterface
{
    const WEBSITE_SEARCH_PLACEHOLDER_REGISTRY = 'oro_website_search.placeholder.registry';
    const WEBSITE_SEARCH_PLACEHOLDER_TAG = 'website_search.placeholder';

    /**
     * {@inheritDoc}
     */
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
