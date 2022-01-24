<?php

namespace Oro\Bundle\CMSBundle\DependencyInjection\Compiler;

use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGPropertiesType;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGStyleType;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGType;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds default values for WYSIWYG DBAL types.
 */
class DbalTypeDefaultValuePass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('oro_platform.provider.dbal_type_default_value')) {
            return;
        }

        $definition = $container->getDefinition('oro_platform.provider.dbal_type_default_value');
        $definition
            ->addMethodCall(
                'addDefaultValuesForDbalTypes',
                [[WYSIWYGType::TYPE => '', WYSIWYGStyleType::TYPE => '', WYSIWYGPropertiesType::TYPE => '[]']]
            );
    }
}
