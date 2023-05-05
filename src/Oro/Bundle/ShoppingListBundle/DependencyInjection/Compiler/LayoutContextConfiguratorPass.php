<?php

namespace Oro\Bundle\ShoppingListBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds allowed routes to the XmlHttpRequest configurator.
 */
class LayoutContextConfiguratorPass implements CompilerPassInterface
{
    private const SERVICE_KEY = 'oro_layout.layout_context_configurator.is_xml_http_request';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(self::SERVICE_KEY)) {
            return;
        }

        $urlProviderDefinition = $container->getDefinition(self::SERVICE_KEY);
        $urlProviderDefinition->addMethodCall('setRoutes', [[
            'oro_shopping_list_frontend_view',
            'oro_shopping_list_frontend_update',
            'oro_shopping_list_frontend_matrix_grid_order'
        ]]);
    }
}
