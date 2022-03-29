<?php

namespace Oro\Bundle\CMSBundle\DependencyInjection\Compiler;

use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGType as DBALWYSIWYGType;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Searches field type provider and extend field guesser by the specified tag
 * and append WYSIWYG field type to the list of extend field types.
 */
class EntityExtendFieldTypePass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $this->addFieldType($container);
        $this->addFieldGuesser($container);
    }

    private function addFieldType(ContainerBuilder $container): void
    {
        if ($container->hasDefinition('oro_entity_extend.field_type_provider')) {
            $definition = $container->getDefinition('oro_entity_extend.field_type_provider');
            $defaultFieldTypes = $definition->getArgument(1);
            $defaultFieldTypes[] = DBALWYSIWYGType::TYPE;
            $definition->replaceArgument(1, $defaultFieldTypes);
        }
    }

    private function addFieldGuesser(ContainerBuilder $container): void
    {
        if ($container->hasDefinition('oro_entity_extend.provider.extend_field_form_type')) {
            $definition = $container->getDefinition('oro_entity_extend.provider.extend_field_form_type');
            $definition->addMethodCall('addExtendTypeMapping', [DBALWYSIWYGType::TYPE, WYSIWYGType::class]);
        }
    }
}
