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
    private const WYSIWYG_FIELD_TYPE_KEY = DBALWYSIWYGType::TYPE;
    private const ENTITY_EXTEND_FIELD_TYPE_PROVIDER_SERVICE_ID = 'oro_entity_extend.field_type_provider';
    private const ENTITY_EXTEND_FIELD_FORM_GUESSER_SERVICE_ID = 'oro_entity_extend.form.guesser.extend_field';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->addFieldType($container);
        $this->addFieldGuesser($container);
    }

    private function addFieldType(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::ENTITY_EXTEND_FIELD_TYPE_PROVIDER_SERVICE_ID)) {
            $definition = $container->getDefinition(self::ENTITY_EXTEND_FIELD_TYPE_PROVIDER_SERVICE_ID);
            $defaultFieldTypes = $definition->getArgument(1);
            $defaultFieldTypes[] = self::WYSIWYG_FIELD_TYPE_KEY;
            $definition->replaceArgument(1, $defaultFieldTypes);
        }
    }

    private function addFieldGuesser(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::ENTITY_EXTEND_FIELD_FORM_GUESSER_SERVICE_ID)) {
            $definition = $container->getDefinition(self::ENTITY_EXTEND_FIELD_FORM_GUESSER_SERVICE_ID);
            $definition->addMethodCall('addExtendTypeMapping', [self::WYSIWYG_FIELD_TYPE_KEY, WYSIWYGType::class]);
        }
    }
}
