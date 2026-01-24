<?php

namespace Oro\Bundle\InventoryBundle\DependencyInjection\Compiler;

use Oro\Bundle\InventoryBundle\DependencyInjection\OroInventoryExtension;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Compiler pass that registers inventory level validation constraints.
 *
 * Adds YAML validation mappings for inventory level entities to the validator builder
 * during container compilation.
 */
class InventoryLevelConstraintPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container)
    {
        $validatorBuilder = $container->getDefinition('validator.builder');
        $validatorBuilder->addMethodCall(
            'addYamlMapping',
            [$container->getParameter(OroInventoryExtension::VALIDATION_CONFIG)]
        );
    }
}
