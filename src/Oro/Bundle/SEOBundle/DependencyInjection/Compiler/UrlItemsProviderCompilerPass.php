<?php

namespace Oro\Bundle\SEOBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;

class UrlItemsProviderCompilerPass implements CompilerPassInterface
{
    /**
     * @var string
     */
    private $service;

    /**
     * @var string
     */
    private $tag;

    /**
     * @param string $service
     * @param string $tag
     */
    public function __construct($service, $tag)
    {
        $this->service = $service;
        $this->tag = $tag;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition($this->service)) {
            return;
        }

        $taggedServices = $container->findTaggedServiceIds($this->tag);
        if (empty($taggedServices)) {
            return;
        }

        $providers = [];
        foreach ($taggedServices as $serviceId => $tags) {
            foreach ($tags as $tagAttrs) {
                if (!isset($tagAttrs['alias'])) {
                    throw new LogicException(sprintf('Could not retrieve "alias" attribute for "%s"', $serviceId));
                }
                $providers[$tagAttrs['alias']] = new Reference($serviceId);
            }
        }

        $container
            ->getDefinition($this->service)
            ->replaceArgument(0, $providers);
    }
}
