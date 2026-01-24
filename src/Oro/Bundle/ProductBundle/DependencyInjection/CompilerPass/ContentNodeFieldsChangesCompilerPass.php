<?php

namespace Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass;

use Oro\Bundle\ProductBundle\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers content node fields to be tracked for changes in services
 * implementing {@see ContentNodeFieldsChangesAwareInterface}.
 *
 * This compiler pass validates that the target service implements the required interface
 * and then registers all configured fields by calling addField() for each one during container compilation.
 */
class ContentNodeFieldsChangesCompilerPass implements CompilerPassInterface
{
    /**
     * @var array
     */
    private $fields;

    /**
     * @var string
     */
    private $fieldsChangesAwareServiceDefinition;

    /**
     * @param array  $fields
     * @param string $fieldsChangesAwareServiceDefinition
     */
    public function __construct(array $fields, $fieldsChangesAwareServiceDefinition)
    {
        $this->fields = $fields;
        $this->fieldsChangesAwareServiceDefinition = $fieldsChangesAwareServiceDefinition;
    }

    /**
     * @throws \Oro\Bundle\ProductBundle\Exception\InvalidArgumentException
     */
    #[\Override]
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition($this->fieldsChangesAwareServiceDefinition)) {
            return;
        }

        $serviceDefinition = $container->getDefinition($this->fieldsChangesAwareServiceDefinition);

        if (!is_a($serviceDefinition->getClass(), ContentNodeFieldsChangesAwareInterface::class, true)) {
            throw new InvalidArgumentException(sprintf(
                'Definition %s has to implement %s',
                $this->fieldsChangesAwareServiceDefinition,
                ContentNodeFieldsChangesAwareInterface::class
            ));
        }

        foreach ($this->fields as $field) {
            $serviceDefinition->addMethodCall('addField', [$field]);
        }
    }
}
