<?php

namespace Oro\Bundle\PricingBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Disables collecting data audit data if the application is not installed yet.
 */
class DisableDataAuditListenerPass implements CompilerPassInterface
{
    private const LISTENER_SERVICE_ID = 'oro_pricing.entity_listener.send_changed_product_prices_to_message_queue';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $isInstalled = $container->hasParameter('installed') && $container->getParameter('installed');
        if (!$isInstalled && $container->hasDefinition(self::LISTENER_SERVICE_ID)) {
            $dataAuditListenerDef = $container->getDefinition(self::LISTENER_SERVICE_ID);
            $dataAuditListenerDef->addMethodCall('setEnabled', [false]);
        }
    }
}
