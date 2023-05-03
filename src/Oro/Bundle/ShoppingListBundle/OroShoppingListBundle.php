<?php

namespace Oro\Bundle\ShoppingListBundle;

use Oro\Bundle\ShoppingListBundle\DependencyInjection\Compiler\LayoutContextConfiguratorPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroShoppingListBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new LayoutContextConfiguratorPass());
    }
}
