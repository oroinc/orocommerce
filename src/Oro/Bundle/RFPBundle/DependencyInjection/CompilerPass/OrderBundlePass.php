<?php

namespace Oro\Bundle\RFPBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OrderBundlePass implements CompilerPassInterface
{
    /** {@inheritdoc} */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasParameter('oro_order.entity.order.class')) {
            return;
        }

        if ($container->hasDefinition('oro_order.form.section.provider')) {
            $container
                ->getDefinition('oro_rfp.form.type.extension.order_line_item_data_storage')
                ->addMethodCall('setSectionProvider', [new Reference('oro_order.form.section.provider')]);
        }
    }
}
