<?php

namespace Oro\Bundle\InventoryBundle\DependencyInjection\Compiler;

use Oro\Bundle\InventoryBundle\DependencyInjection\OroInventoryExtension;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class InventoryLevelConstraintPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $validatorBuilder = $container->getDefinition('validator.builder');
        $validatorBuilder->addMethodCall(
            'addYamlMapping',
            [$container->getParameter(OroInventoryExtension::VALIDATION_CONFIG)]
        );
    }
}
