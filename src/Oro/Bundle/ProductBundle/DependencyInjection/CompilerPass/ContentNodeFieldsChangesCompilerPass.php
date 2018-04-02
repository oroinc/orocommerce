<?php

namespace Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass;

use Oro\Bundle\ProductBundle\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
     * {@inheritdoc}
     * @throws \Oro\Bundle\ProductBundle\Exception\InvalidArgumentException
     */
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
