<?php

namespace Oro\Bundle\InventoryBundle;

use Oro\Bundle\InventoryBundle\DependencyInjection\Compiler\InventoryLevelConstraintPass;
use Oro\Bundle\InventoryBundle\DependencyInjection\Compiler\InventoryLevelMigrationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroInventoryBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new InventoryLevelMigrationPass());
        $container->addCompilerPass(new InventoryLevelConstraintPass());
    }
}
