<?php

namespace Oro\Bundle\CMSBundle\DependencyInjection\Compiler;

use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGType;
use Oro\Bundle\CMSBundle\Validator\Constraints\WYSIWYG;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Add WYSIWYG validation to all extend fields with type WYSIWYG.
 * Works for form, API and import validation.
 */
class ExtendFieldValidationLoaderPass implements CompilerPassInterface
{
    private const ENTITY_EXTEND_FIELD_VALIDATION_LOADER_SERVICE_ID = 'oro_entity_extend.validation_loader';
    private const SERIALIZED_FIELDS_EXTEND_FIELD_VALIDATOR_SERVICE_ID
        = 'oro_serialized_fields.validator.extend_entity_serialized_data';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->addTableFieldConstraints($container);
        $this->addSerializedFieldConstraints($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    private function addTableFieldConstraints(ContainerBuilder $container): void
    {
        if ($container->hasDefinition(self::ENTITY_EXTEND_FIELD_VALIDATION_LOADER_SERVICE_ID)) {
            $definition = $container->getDefinition(self::ENTITY_EXTEND_FIELD_VALIDATION_LOADER_SERVICE_ID);
            $definition->addMethodCall('addConstraints', [WYSIWYGType::TYPE, [[WYSIWYG::class => null]]]);
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    private function addSerializedFieldConstraints(ContainerBuilder $container): void
    {
        if ($container->hasDefinition(self::SERIALIZED_FIELDS_EXTEND_FIELD_VALIDATOR_SERVICE_ID)) {
            $definition = $container->getDefinition(self::SERIALIZED_FIELDS_EXTEND_FIELD_VALIDATOR_SERVICE_ID);
            $definition->addMethodCall('addConstraints', [WYSIWYGType::TYPE, [[WYSIWYG::class => null]]]);
        }
    }
}
