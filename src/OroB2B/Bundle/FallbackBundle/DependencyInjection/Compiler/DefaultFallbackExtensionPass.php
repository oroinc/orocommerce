<?php

namespace OroB2B\Bundle\FallbackBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DefaultFallbackExtensionPass implements CompilerPassInterface
{
    const GENERATOR_EXTENSION_NAME = 'orob2b_fallback.entity_generator.extension';

    protected $options;
    protected $classes;

    public function __construct(array $options = [], array $classes = [])
    {
        $this->options = $options;
        $this->classes = $classes;
    }

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $generator = $container->getDefinition(self::GENERATOR_EXTENSION_NAME);

        if(!$this->classes || !$this->options) {
            return;
        }

        foreach ($this->classes as $class) {
            $generator->addMethodCall('addMethodExtension', [$class, $this->options]);
        }
    }
}
