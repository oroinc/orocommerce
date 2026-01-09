<?php

namespace Oro\Bundle\SEOBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers URL item providers with a configurable service and tag.
 *
 * This compiler pass collects all services tagged with a specified tag and registers them with a target service.
 * It is designed to be flexible and reusable for different provider registries by accepting the service name
 * and tag name as constructor parameters. This allows multiple instances to handle different provider types
 * (e.g., regular providers, access denied providers) with a single implementation.
 */
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

    #[\Override]
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
