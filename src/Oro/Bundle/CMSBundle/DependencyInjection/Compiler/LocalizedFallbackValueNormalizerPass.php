<?php

namespace Oro\Bundle\CMSBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds WYSIWYG fields to the name map of the "oro_locale.cache.normalizer.localized_fallback_value" service.
 */
class LocalizedFallbackValueNormalizerPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        $normalizerDef = $container->getDefinition('oro_locale.cache.normalizer.localized_fallback_value');
        $nameMap = $normalizerDef->getArgument(0);
        $nameMap['wysiwyg'] = 'w';
        $nameMap['wysiwyg_style'] = 'ws';
        $nameMap['wysiwyg_properties'] = 'wp';
        $normalizerDef->setArgument(0, $nameMap);
    }
}
