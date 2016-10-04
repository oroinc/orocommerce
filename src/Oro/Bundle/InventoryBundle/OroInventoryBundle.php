<?php

namespace Oro\Bundle\InventoryBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\InventoryBundle\DependencyInjection\Compiler\InventoryLevelMigrationPass;

class OroInventoryBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new InventoryLevelMigrationPass());
    }
}
