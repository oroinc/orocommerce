<?php

namespace Oro\Bundle\InventoryBundle\DependencyInjection\Compiler;

use Oro\Bundle\InventoryBundle\DependencyInjection\OroInventoryExtension;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Parameter;

class InventoryLevelConstraintPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $validatorBuilder = $container->getDefinition('validator.builder');
        $validatorBuilder->addMethodCall('addYamlMapping', [new Parameter(OroInventoryExtension::VALIDATION_CONFIG)]);
    }
}
