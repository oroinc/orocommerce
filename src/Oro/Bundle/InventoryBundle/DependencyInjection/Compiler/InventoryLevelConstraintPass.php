<?php

namespace Oro\Bundle\InventoryBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Parameter;

use Oro\Bundle\InventoryBundle\DependencyInjection\OroInventoryExtension;

class InventoryLevelConstraintPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $validatorBuilder = $container->getDefinition('validator.builder');
        $validatorBuilder->addMethodCall('addYamlMapping', [new Parameter(OroInventoryExtension::VALIDATION_CONFIG)]);
    }
}
